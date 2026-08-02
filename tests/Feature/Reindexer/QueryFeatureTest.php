<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;

/**
 * SQL (GET/POST) and Query-DSL (POST) execution against a real server:
 * sorting, limits, offsets, aggregations, error responses.
 */
class QueryFeatureTest extends FeatureCase
{
    private string $ns = 'query_ns';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createNamespace($this->ns, [
            $this->intPkIndex(),
            $this->index('rating', FieldType::INT, IndexType::TREE),
            $this->index('author', FieldType::STRING, IndexType::HASH),
        ]);

        $items = $this->itemService($this->ns);
        $data = [
            ['id' => 1, 'rating' => 10, 'author' => 'alice'],
            ['id' => 2, 'rating' => 20, 'author' => 'bob'],
            ['id' => 3, 'rating' => 30, 'author' => 'alice'],
            ['id' => 4, 'rating' => 40, 'author' => 'bob'],
            ['id' => 5, 'rating' => 50, 'author' => 'alice'],
        ];
        foreach ($data as $item) {
            $this->assertSame(200, $items->add($item)->getCode());
        }
    }

    public function testSortAndLimitAndOffsetViaSqlGet(): void
    {
        $body = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY rating DESC LIMIT 2 OFFSET 1")
            ->getDecodedResponseBody(true);

        $this->assertSame([40, 30], array_column($body['items'], 'rating'));
    }

    public function testCountAggregationViaSqlGet(): void
    {
        $body = $this->queryService
            ->createByHttpGet("SELECT count(*) FROM {$this->ns}")
            ->getDecodedResponseBody(true);

        $this->assertSame([], $body['items']);
        $this->assertSame('count', $body['aggregations'][0]['type']);
        $this->assertSame(5.0, $body['aggregations'][0]['value']);
    }

    public function testSumAndMinMaxAggregations(): void
    {
        $sum = $this->queryService
            ->createByHttpGet("SELECT sum(rating) FROM {$this->ns}")
            ->getDecodedResponseBody(true);
        $this->assertSame(150.0, $sum['aggregations'][0]['value']);
        $this->assertSame(['rating'], $sum['aggregations'][0]['fields']);

        $max = $this->queryService
            ->createByHttpGet("SELECT max(rating) FROM {$this->ns}")
            ->getDecodedResponseBody(true);
        $this->assertSame(50.0, $max['aggregations'][0]['value']);

        $min = $this->queryService
            ->createByHttpGet("SELECT min(rating) FROM {$this->ns}")
            ->getDecodedResponseBody(true);
        $this->assertSame(10.0, $min['aggregations'][0]['value']);
    }

    public function testFilterByStringEquality(): void
    {
        $body = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE author = 'alice' ORDER BY id")
            ->getDecodedResponseBody(true);

        $this->assertSame([1, 3, 5], array_column($body['items'], 'id'));
    }

    public function testSqlQueryViaPost(): void
    {
        $body = $this->queryService
            ->createSqlQueryByHttpPost("SELECT * FROM {$this->ns} WHERE rating >= 30 ORDER BY rating")
            ->getDecodedResponseBody(true);

        $this->assertSame([30, 40, 50], array_column($body['items'], 'rating'));
    }

    public function testSdlQueryViaPostWithArrayDsl(): void
    {
        $response = $this->queryService->createSdlQueryByHttpPost([
            'namespace' => $this->ns,
            'limit' => 2,
            'sort' => ['field' => 'rating', 'desc' => true],
            'filters' => [
                ['field' => 'rating', 'cond' => 'GT', 'value' => 10],
            ],
        ]);

        $this->assertSame(200, $response->getCode(), $response->getResponseBody());
        $this->assertSame([50, 40], array_column($response->getDecodedResponseBody(true)['items'], 'rating'));
    }

    public function testSdlQueryViaPostWithRawJsonString(): void
    {
        $response = $this->queryService->createSdlQueryByHttpPost(
            json_encode(['namespace' => $this->ns, 'limit' => 1, 'sort' => ['field' => 'id', 'desc' => false]])
        );

        $this->assertSame(200, $response->getCode(), $response->getResponseBody());
        $this->assertSame([1], array_column($response->getDecodedResponseBody(true)['items'], 'id'));
    }

    public function testInvalidSqlReturns400WithDescription(): void
    {
        $response = $this->queryService->createByHttpGet('SELEKT nonsense FROM nowhere');

        $this->assertSame(400, $response->getCode());
        $body = $response->getDecodedResponseBody(true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Syntax error', $body['description']);
    }

    public function testQueryAgainstMissingNamespaceReturnsError(): void
    {
        $response = $this->queryService->createByHttpGet('SELECT * FROM missing_ns_404');

        $this->assertGreaterThanOrEqual(400, $response->getCode());
        $this->assertFalse($response->getDecodedResponseBody(true)['success']);
    }

    public function testUnicodeValueInWhereClause(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 100, 'rating' => 1, 'author' => 'фёдор_достоевский']);

        $body = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE author = 'фёдор_достоевский'")
            ->getDecodedResponseBody(true);

        $this->assertSame([100], array_column($body['items'], 'id'));
    }

    public function testFacetAggregationOverFilteredSetViaDsl(): void
    {
        $body = $this->queryService->createSdlQueryByHttpPost([
            'namespace' => $this->ns,
            'filters' => [['field' => 'rating', 'cond' => 'GE', 'value' => 20]],
            'aggregations' => [['type' => 'facet', 'fields' => ['author']]],
        ])->getDecodedResponseBody(true);

        $this->assertSame('facet', $body['aggregations'][0]['type']);
        $this->assertSame(['author'], $body['aggregations'][0]['fields']);

        $facets = [];
        foreach ($body['aggregations'][0]['facets'] as $facet) {
            $facets[$facet['values'][0]] = $facet['count'];
        }
        // rating >= 20 leaves alice ×2 (30, 50) and bob ×2 (20, 40)
        $this->assertSame(['alice' => 2, 'bob' => 2], $facets);
    }
}
