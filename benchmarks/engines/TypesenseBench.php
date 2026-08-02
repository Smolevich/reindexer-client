<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks\Engines;

use PhpBench\Attributes as Bench;
use Reindexer\Benchmarks\Engines\Support\EngineDataset;
use Typesense\Client;

/**
 * Typesense side of the engine comparison, see docs/benchmarks-engines.md.
 * Same six scenarios as the other engine benches; every subject fully reads
 * and decodes the response body.
 *
 * Requires a loaded hf_models collection (benchmarks/engines/load-typesense.php)
 * and TYPESENSE_HOST / TYPESENSE_API_KEY set.
 */
#[Bench\BeforeMethods('setUp')]
class TypesenseBench
{
    private Client $client;
    private string $collection;

    /** @var string[] */
    private array $ids = [];
    private int $idCursor = 0;
    private int $ftCursor = 0;
    private int $dateFrom = 0;

    public function setUp(): void
    {
        $host = parse_url(EngineDataset::requireEnv('TYPESENSE_HOST'));
        $this->client = new Client([
            'api_key' => EngineDataset::requireEnv('TYPESENSE_API_KEY'),
            'nodes' => [[
                'host' => $host['host'],
                'port' => $host['port'] ?? 8108,
                'protocol' => $host['scheme'] ?? 'http',
            ]],
            'connection_timeout_seconds' => 120,
        ]);
        $this->collection = EngineDataset::COLLECTION;
        $this->ids = EngineDataset::sampleIds();
        $this->dateFrom = EngineDataset::dateFrom();
    }

    #[Bench\Revs(200)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchPointLookupById(): void
    {
        $id = $this->ids[$this->idCursor++ % count($this->ids)];
        $doc = $this->client->collections[$this->collection]->documents[EngineDataset::uid($id)]->retrieve();
        if (($doc['model_id'] ?? null) !== $id) {
            throw new \RuntimeException("Document $id not found");
        }
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFilterSort(): void
    {
        $this->search([
            'q' => '*',
            'filter_by' => sprintf(
                'downloads:>%d && pipeline_tag:=%s',
                EngineDataset::FILTER_DOWNLOADS,
                EngineDataset::FILTER_PIPELINE_TAG
            ),
            'sort_by' => 'likes:desc',
            'per_page' => EngineDataset::RESULT_LIMIT,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchTagsContains(): void
    {
        $this->search([
            'q' => '*',
            'filter_by' => sprintf('tags:=%s', EngineDataset::POPULAR_TAG),
            'per_page' => EngineDataset::RESULT_LIMIT,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchDateRange(): void
    {
        $this->search([
            'q' => '*',
            'filter_by' => sprintf('created_ts:>%d', $this->dateFrom),
            'sort_by' => 'created_ts:desc',
            'per_page' => EngineDataset::RESULT_LIMIT,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFulltext(): void
    {
        $term = EngineDataset::FULLTEXT_TERMS[$this->ftCursor++ % count(EngineDataset::FULLTEXT_TERMS)];
        $hits = $this->search([
            'q' => $term,
            'query_by' => 'model_id,author',
            'per_page' => EngineDataset::RESULT_LIMIT,
        ]);
        if ($hits === []) {
            throw new \RuntimeException('Fulltext search returned no results');
        }
    }

    #[Bench\Revs(10)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFacetFiltered(): void
    {
        $response = $this->client->collections[$this->collection]->documents->search([
            'q' => '*',
            'filter_by' => sprintf('downloads:>%d', EngineDataset::FACET_FILTER_DOWNLOADS),
            'facet_by' => 'pipeline_tag',
            'max_facet_values' => 100,
            'per_page' => 1,
        ]);
        if (!isset($response['facet_counts'][0]['counts'])) {
            throw new \RuntimeException('Facet counts missing in response');
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, mixed> hits
     */
    private function search(array $params): array
    {
        $response = $this->client->collections[$this->collection]->documents->search($params);
        if (!isset($response['hits'])) {
            throw new \RuntimeException('Unexpected search payload');
        }

        return $response['hits'];
    }
}
