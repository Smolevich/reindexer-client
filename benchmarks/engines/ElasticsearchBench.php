<?php

declare(strict_types=1);

namespace Reindexer\Benchmarks\Engines;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use PhpBench\Attributes as Bench;
use Reindexer\Benchmarks\Engines\Support\EngineDataset;

/**
 * Elasticsearch side of the engine comparison, see docs/benchmarks-engines.md.
 * Same six scenarios as the other engine benches; every subject fully reads
 * and decodes the response body.
 *
 * Requires a loaded hf_models index (benchmarks/engines/load-elasticsearch.php)
 * and ELASTICSEARCH_HOST set.
 */
#[Bench\BeforeMethods('setUp')]
class ElasticsearchBench
{
    private Client $client;
    private string $index;

    /** @var string[] */
    private array $ids = [];
    private int $idCursor = 0;
    private int $ftCursor = 0;
    private int $dateFrom = 0;

    public function setUp(): void
    {
        $this->client = ClientBuilder::create()
            ->setHosts([EngineDataset::requireEnv('ELASTICSEARCH_HOST')])
            ->build();
        $this->index = EngineDataset::COLLECTION;
        $this->ids = EngineDataset::sampleIds();
        $this->dateFrom = EngineDataset::dateFrom();
    }

    #[Bench\Revs(200)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchPointLookupById(): void
    {
        $id = $this->ids[$this->idCursor++ % count($this->ids)];
        $doc = $this->client->get(['index' => $this->index, 'id' => $id])->asArray();
        if (($doc['found'] ?? false) !== true) {
            throw new \RuntimeException("Document $id not found");
        }
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFilterSort(): void
    {
        $this->search([
            'query' => ['bool' => ['filter' => [
                ['range' => ['downloads' => ['gt' => EngineDataset::FILTER_DOWNLOADS]]],
                ['term' => ['pipeline_tag' => EngineDataset::FILTER_PIPELINE_TAG]],
            ]]],
            'sort' => [['likes' => 'desc']],
            'size' => EngineDataset::RESULT_LIMIT,
            'track_total_hits' => false,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchTagsContains(): void
    {
        $this->search([
            'query' => ['bool' => ['filter' => [
                ['term' => ['tags' => EngineDataset::POPULAR_TAG]],
            ]]],
            'size' => EngineDataset::RESULT_LIMIT,
            'track_total_hits' => false,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchDateRange(): void
    {
        $this->search([
            'query' => ['bool' => ['filter' => [
                ['range' => ['created_ts' => ['gt' => $this->dateFrom]]],
            ]]],
            'sort' => [['created_ts' => 'desc']],
            'size' => EngineDataset::RESULT_LIMIT,
            'track_total_hits' => false,
        ]);
    }

    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    public function benchFulltext(): void
    {
        $term = EngineDataset::FULLTEXT_TERMS[$this->ftCursor++ % count(EngineDataset::FULLTEXT_TERMS)];
        $hits = $this->search([
            'query' => ['multi_match' => ['query' => $term, 'fields' => ['id.text', 'author.text']]],
            'size' => EngineDataset::RESULT_LIMIT,
            'track_total_hits' => false,
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
        $response = $this->client->search(['index' => $this->index, 'body' => [
            'query' => ['bool' => ['filter' => [
                ['range' => ['downloads' => ['gt' => EngineDataset::FACET_FILTER_DOWNLOADS]]],
            ]]],
            'aggs' => ['by_pipeline_tag' => ['terms' => ['field' => 'pipeline_tag', 'size' => 100]]],
            'size' => 0,
            'track_total_hits' => false,
        ]])->asArray();
        if (!isset($response['aggregations']['by_pipeline_tag']['buckets'])) {
            throw new \RuntimeException('Facet aggregation missing in response');
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<int, mixed> hits
     */
    private function search(array $body): array
    {
        $response = $this->client->search(['index' => $this->index, 'body' => $body])->asArray();
        if (!isset($response['hits']['hits'])) {
            throw new \RuntimeException('Unexpected search payload');
        }

        return $response['hits']['hits'];
    }
}
