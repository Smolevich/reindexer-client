<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks\Engines;

use PhpBench\Attributes as Bench;
use Reindexer\Benchmarks\Engines\Support\EngineDataset;
use Reindexer\Benchmarks\Support\HfModels;
use Reindexer\Client\Api;
use Reindexer\Services\Query;

/**
 * Reindexer (HTTP transport) side of the engine comparison, see
 * docs/benchmarks-engines.md. Same six scenarios as the other engine benches;
 * every subject fully reads and JSON-decodes the result set.
 *
 * Requires a loaded hf_models namespace (composer bench-load) and
 * REINDEXER_HOST set.
 */
#[Bench\BeforeMethods('setUp')]
class ReindexerHttpBench
{
    private Query $query;
    private string $ns;

    /** @var string[] */
    private array $ids = [];
    private int $idCursor = 0;
    private int $ftCursor = 0;
    private int $dateFrom = 0;

    public function setUp(): void
    {
        $api = new Api(EngineDataset::requireEnv('REINDEXER_HOST'), ['http_errors' => false, 'timeout' => 120]);
        $this->query = new Query($api);
        $this->query->setDatabase(HfModels::DEFAULT_DB);
        $this->ns = EngineDataset::COLLECTION;
        $this->ids = EngineDataset::sampleIds();
        $this->dateFrom = EngineDataset::dateFrom();
    }

    #[Bench\Revs(200)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchPointLookupById(): void
    {
        $id = $this->ids[$this->idCursor++ % count($this->ids)];
        $items = $this->select(sprintf("SELECT * FROM %s WHERE id = '%s' LIMIT 1", $this->ns, $id));
        if (count($items) !== 1) {
            throw new \RuntimeException('Point lookup must return exactly one item');
        }
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFilterSort(): void
    {
        $this->select(sprintf(
            "SELECT * FROM %s WHERE downloads > %d AND pipeline_tag = '%s' ORDER BY likes DESC LIMIT %d",
            $this->ns,
            EngineDataset::FILTER_DOWNLOADS,
            EngineDataset::FILTER_PIPELINE_TAG,
            EngineDataset::RESULT_LIMIT
        ));
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchTagsContains(): void
    {
        $this->select(sprintf(
            "SELECT * FROM %s WHERE tags IN ('%s') LIMIT %d",
            $this->ns,
            EngineDataset::POPULAR_TAG,
            EngineDataset::RESULT_LIMIT
        ));
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchDateRange(): void
    {
        $this->select(sprintf(
            'SELECT * FROM %s WHERE created_ts > %d ORDER BY created_ts DESC LIMIT %d',
            $this->ns,
            $this->dateFrom,
            EngineDataset::RESULT_LIMIT
        ));
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFulltext(): void
    {
        $term = EngineDataset::FULLTEXT_TERMS[$this->ftCursor++ % count(EngineDataset::FULLTEXT_TERMS)];
        $items = $this->select(sprintf(
            'SELECT * FROM %s WHERE "id+author" = \'%s\' LIMIT %d',
            $this->ns,
            $term,
            EngineDataset::RESULT_LIMIT
        ));
        if ($items === []) {
            throw new \RuntimeException('Fulltext search returned no results');
        }
    }

    #[Bench\Revs(10)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFacetFiltered(): void
    {
        $sql = sprintf(
            'SELECT FACET(pipeline_tag) FROM %s WHERE downloads > %d',
            $this->ns,
            EngineDataset::FACET_FILTER_DOWNLOADS
        );
        $response = $this->query->createByHttpGet($sql);
        if ($response->getCode() !== 200) {
            throw new \RuntimeException("Facet query failed ({$response->getCode()}): {$response->getResponseBody()}");
        }
        $decoded = json_decode((string) $response->getResponseBody(), true);
        if (!isset($decoded['aggregations'][0]['facets'])) {
            throw new \RuntimeException('Facet aggregation missing in response');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function select(string $sql): array
    {
        $response = $this->query->createByHttpGet($sql);
        if ($response->getCode() !== 200) {
            throw new \RuntimeException("Query failed ({$response->getCode()}): {$response->getResponseBody()}");
        }
        $decoded = json_decode((string) $response->getResponseBody(), true);
        if (!is_array($decoded) || !array_key_exists('items', $decoded)) {
            throw new \RuntimeException('Unexpected query payload');
        }

        return $decoded['items'];
    }
}
