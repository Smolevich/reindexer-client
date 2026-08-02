<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks;

use PhpBench\Attributes as Bench;
use Reindexer\Benchmarks\Support\HfModels;
use Reindexer\Client\Api;
use Reindexer\Grpc\ModifyMode;
use Reindexer\Services\Query;
use Reindexer\Transport\Grpc\GrpcClient;

/**
 * HTTP vs gRPC transport benchmarks on the hf_models dataset
 * (Hugging Face Hub model catalog, see docs/benchmarks.md).
 *
 * Requires: seeded data (composer bench-seed) loaded into reindexer
 * (composer bench-load) and both REINDEXER_HOST / REINDEXER_GRPC_TARGET set.
 *
 * Every subject fully reads and JSON-decodes the result set on both
 * transports, so the measured cost includes payload deserialization.
 */
#[Bench\BeforeMethods('setUp')]
class TransportBench
{
    private const BULK_NS = 'hf_models_bulk';
    private const HTTP_BULK_BATCH = 500;
    private const FULLTEXT_TERMS = ['bert', 'llama'];

    private Api $api;
    private Query $query;
    private GrpcClient $grpc;
    private string $db;
    private string $ns;

    /** @var string[] primary keys sampled from the dataset */
    private array $ids = [];
    private int $idCursor = 0;
    private int $ftCursor = 0;
    private int $dateFrom = 0;

    /** @var string[][] HTTP bulk payloads: batches of raw JSON lines */
    private array $bulkBatches = [];

    /** @var string[] gRPC bulk payload: flat list of raw JSON lines */
    private array $bulkItems = [];

    private bool $bulkFirstRev = true;

    public function setUp(): void
    {
        $host = (string) getenv('REINDEXER_HOST');
        $target = (string) getenv('REINDEXER_GRPC_TARGET');
        if ($host === '' || $target === '') {
            throw new \RuntimeException('REINDEXER_HOST and REINDEXER_GRPC_TARGET must be set');
        }

        $this->db = getenv('BENCH_DB') !== false ? (string) getenv('BENCH_DB') : HfModels::DEFAULT_DB;
        $this->ns = getenv('BENCH_NS') !== false ? (string) getenv('BENCH_NS') : HfModels::DEFAULT_NS;

        $this->api = new Api($host, ['http_errors' => false]);
        $this->query = new Query($this->api);
        $this->query->setDatabase($this->db);

        $this->grpc = new GrpcClient($target);
        $this->grpc->connect($this->db);

        $this->dateFrom = time() - 90 * 86400;

        $items = $this->httpSelect(sprintf('SELECT id FROM %s LIMIT 1000', $this->ns));
        $this->ids = array_column($items, 'id');
        if ($this->ids === []) {
            throw new \RuntimeException("Namespace {$this->ns} is empty. Run: composer bench-load");
        }
    }

    /**
     * Reads bulk payload from the seeded NDJSON file and recreates the
     * scratch namespace, so every iteration inserts into an empty namespace.
     */
    public function setUpBulk(): void
    {
        $file = getenv('BENCH_DATA_FILE') !== false
            ? (string) getenv('BENCH_DATA_FILE')
            : HfModels::DEFAULT_DATA_FILE;
        $count = max(1, (int) (getenv('BENCH_BULK_COUNT') ?: 5000));

        $this->bulkItems = iterator_to_array(HfModels::readNdjson($file, $count), false);
        if (count($this->bulkItems) < $count) {
            throw new \RuntimeException(sprintf(
                'Data file %s has only %d records, %d required. Run: composer bench-seed',
                $file,
                count($this->bulkItems),
                $count
            ));
        }
        $this->bulkBatches = array_chunk($this->bulkItems, self::HTTP_BULK_BATCH);

        HfModels::recreateNamespaceHttp($this->api, $this->db, self::BULK_NS);
    }

    // -- bulk insert: 5000 records ------------------------------------------

    #[Bench\BeforeMethods(['setUp', 'setUpBulk'])]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(0)]
    public function benchBulkInsert5000Http(): void
    {
        // The scratch namespace is empty only before the first rev of the
        // process (setUpBulk recreates it per iteration), so the "all records
        // inserted" assertion only holds there.
        $firstRev = $this->bulkFirstRev;
        $this->bulkFirstRev = false;

        $inserted = 0;
        foreach ($this->bulkBatches as $batch) {
            $response = $this->api->request(
                'POST',
                sprintf('/api/v1/db/%s/namespaces/%s/items', $this->db, self::BULK_NS),
                implode("\n", $batch),
                ['Content-Type' => 'application/json']
            );
            if ($response->getCode() !== 200) {
                throw new \RuntimeException('Bulk insert failed: ' . $response->getResponseBody());
            }
            $decoded = json_decode((string) $response->getResponseBody(), true);
            $inserted += (int) ($decoded['updated'] ?? 0);
        }
        if ($firstRev && $inserted !== count($this->bulkItems)) {
            throw new \RuntimeException("Expected to insert all records, got $inserted");
        }
    }

    #[Bench\BeforeMethods(['setUp', 'setUpBulk'])]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(0)]
    public function benchBulkInsert5000Grpc(): void
    {
        // Same INSERT mode as the HTTP POST /items subject.
        $this->grpc->modifyItems(self::BULK_NS, $this->bulkItems, ModifyMode::INSERT);
    }

    // -- point select by PK -------------------------------------------------

    #[Bench\Revs(200)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchPointSelectByPkHttp(): void
    {
        $items = $this->httpSelect($this->pointSelectSql());
        if (count($items) !== 1) {
            throw new \RuntimeException('Point select must return exactly one item');
        }
    }

    #[Bench\Revs(200)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchPointSelectByPkGrpc(): void
    {
        $items = $this->grpcSelect($this->pointSelectSql());
        if (count($items) !== 1) {
            throw new \RuntimeException('Point select must return exactly one item');
        }
    }

    // -- filter + sort ------------------------------------------------------

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFilterSortHttp(): void
    {
        $this->httpSelect($this->filterSortSql());
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFilterSortGrpc(): void
    {
        $this->grpcSelect($this->filterSortSql());
    }

    // -- array condition: tags contains ------------------------------------

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchTagsContainsHttp(): void
    {
        $this->httpSelect($this->tagsContainsSql());
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchTagsContainsGrpc(): void
    {
        $this->grpcSelect($this->tagsContainsSql());
    }

    // -- date range + sort --------------------------------------------------

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchDateRangeHttp(): void
    {
        $this->httpSelect($this->dateRangeSql());
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchDateRangeGrpc(): void
    {
        $this->grpcSelect($this->dateRangeSql());
    }

    // -- fulltext search ----------------------------------------------------

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFulltextHttp(): void
    {
        $this->httpSelect($this->fulltextSql());
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFulltextGrpc(): void
    {
        $this->grpcSelect($this->fulltextSql());
    }

    // -- query builders (shared between transports) -------------------------

    private function pointSelectSql(): string
    {
        $id = $this->ids[$this->idCursor++ % count($this->ids)];

        return sprintf("SELECT * FROM %s WHERE id = '%s' LIMIT 1", $this->ns, $id);
    }

    private function filterSortSql(): string
    {
        return sprintf(
            "SELECT * FROM %s WHERE downloads > 100000 AND pipeline_tag = 'text-generation' ORDER BY likes DESC LIMIT 100",
            $this->ns
        );
    }

    private function tagsContainsSql(): string
    {
        return sprintf("SELECT * FROM %s WHERE tags IN ('llama') LIMIT 100", $this->ns);
    }

    private function dateRangeSql(): string
    {
        return sprintf(
            'SELECT * FROM %s WHERE created_ts > %d ORDER BY created_ts DESC LIMIT 100',
            $this->ns,
            $this->dateFrom
        );
    }

    private function fulltextSql(): string
    {
        $term = self::FULLTEXT_TERMS[$this->ftCursor++ % count(self::FULLTEXT_TERMS)];

        return sprintf('SELECT * FROM %s WHERE "id+author" = \'%s\' LIMIT 100', $this->ns, $term);
    }

    // -- transport helpers --------------------------------------------------

    /**
     * @return array<int, array<string, mixed>> decoded items
     */
    private function httpSelect(string $sql): array
    {
        $response = $this->query->createByHttpGet($sql);
        if ($response->getCode() !== 200) {
            throw new \RuntimeException("HTTP query failed ({$response->getCode()}): {$response->getResponseBody()}");
        }

        $decoded = json_decode((string) $response->getResponseBody(), true);
        if (!is_array($decoded) || !array_key_exists('items', $decoded)) {
            throw new \RuntimeException('Unexpected HTTP query payload');
        }

        return $decoded['items'];
    }

    /**
     * @return array<int, array<string, mixed>> decoded items
     */
    private function grpcSelect(string $sql): array
    {
        return iterator_to_array($this->grpc->execSql($sql), false);
    }
}
