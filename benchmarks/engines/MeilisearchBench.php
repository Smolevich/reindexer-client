<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks\Engines;

use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use PhpBench\Attributes as Bench;
use Reindexer\Benchmarks\Engines\Support\EngineDataset;

/**
 * Meilisearch side of the engine comparison, see docs/benchmarks-engines.md.
 * Same six scenarios as the other engine benches; every subject fully reads
 * and decodes the response body.
 *
 * Requires a loaded hf_models index (benchmarks/engines/load-meilisearch.php)
 * and MEILISEARCH_HOST / MEILISEARCH_API_KEY set.
 */
#[Bench\BeforeMethods('setUp')]
class MeilisearchBench
{
    private Indexes $index;

    /** @var string[] */
    private array $ids = [];
    private int $idCursor = 0;
    private int $ftCursor = 0;
    private int $dateFrom = 0;

    public function setUp(): void
    {
        $client = new Client(
            EngineDataset::requireEnv('MEILISEARCH_HOST'),
            EngineDataset::requireEnv('MEILISEARCH_API_KEY')
        );
        $this->index = $client->index(EngineDataset::COLLECTION);
        $this->ids = EngineDataset::sampleIds();
        $this->dateFrom = EngineDataset::dateFrom();
    }

    #[Bench\Revs(200)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchPointLookupById(): void
    {
        $id = $this->ids[$this->idCursor++ % count($this->ids)];
        $doc = $this->index->getDocument(EngineDataset::uid($id));
        if (($doc['id'] ?? null) !== $id) {
            throw new \RuntimeException("Document $id not found");
        }
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFilterSort(): void
    {
        $this->search('', [
            'filter' => sprintf(
                'downloads > %d AND pipeline_tag = "%s"',
                EngineDataset::FILTER_DOWNLOADS,
                EngineDataset::FILTER_PIPELINE_TAG
            ),
            'sort' => ['likes:desc'],
            'limit' => EngineDataset::RESULT_LIMIT,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchTagsContains(): void
    {
        $this->search('', [
            'filter' => sprintf('tags = "%s"', EngineDataset::POPULAR_TAG),
            'limit' => EngineDataset::RESULT_LIMIT,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchDateRange(): void
    {
        $this->search('', [
            'filter' => sprintf('created_ts > %d', $this->dateFrom),
            'sort' => ['created_ts:desc'],
            'limit' => EngineDataset::RESULT_LIMIT,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFulltext(): void
    {
        $term = EngineDataset::FULLTEXT_TERMS[$this->ftCursor++ % count(EngineDataset::FULLTEXT_TERMS)];
        $hits = $this->search($term, ['limit' => EngineDataset::RESULT_LIMIT]);
        if ($hits === []) {
            throw new \RuntimeException('Fulltext search returned no results');
        }
    }

    #[Bench\Revs(10)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFacetFiltered(): void
    {
        $result = $this->index->search('', [
            'filter' => sprintf('downloads > %d', EngineDataset::FACET_FILTER_DOWNLOADS),
            'facets' => ['pipeline_tag'],
            'limit' => 1,
        ])->toArray();
        if (!isset($result['facetDistribution']['pipeline_tag'])) {
            throw new \RuntimeException('Facet distribution missing in response');
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, mixed> hits
     */
    private function search(string $query, array $params): array
    {
        $result = $this->index->search($query, $params)->toArray();
        if (!isset($result['hits'])) {
            throw new \RuntimeException('Unexpected search payload');
        }

        return $result['hits'];
    }
}
