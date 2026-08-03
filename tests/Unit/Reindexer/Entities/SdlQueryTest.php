<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Entities;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Entities\Entity;
use Reindexer\Entities\SdlQuery;

#[CoversClass(SdlQuery::class)]
#[CoversClass(Entity::class)]
class SdlQueryTest extends TestCase
{
    public function testEmptyQuerySerializesToEmptyBody(): void
    {
        $this->assertSame([], (new SdlQuery())->getBody());
    }

    public function testFullQuerySerializesWithSnakeCaseKeys(): void
    {
        $query = new class () extends SdlQuery {
            public function __construct()
            {
                $this->namespace = 'items';
                $this->limit = 10;
                $this->offset = 5;
                $this->distinct = 'name';
                $this->reqTotal = true;
                $this->filters = [['field' => 'id', 'cond' => 'EQ', 'value' => 1]];
                $this->sort = ['field' => 'id', 'desc' => true];
                $this->selectFilter = ['id', 'name'];
                $this->selectFunctions = ['name = snippet(<b>,</b>,5,5)'];
                $this->aggregations = [['fields' => ['id'], 'type' => 'sum']];
            }
        };

        $this->assertSame(
            [
                'namespace' => 'items',
                'limit' => 10,
                'offset' => 5,
                'distinct' => 'name',
                'req_total' => true,
                'filters' => [['field' => 'id', 'cond' => 'EQ', 'value' => 1]],
                'sort' => ['field' => 'id', 'desc' => true],
                'select_filter' => ['id', 'name'],
                'select_functions' => ['name = snippet(<b>,</b>,5,5)'],
                'aggregations' => [['fields' => ['id'], 'type' => 'sum']],
            ],
            $query->getBody()
        );
    }

    public function testZeroAndNegativeLimitsAreSerializedAsIs(): void
    {
        $query = new class () extends SdlQuery {
            public function __construct()
            {
                $this->namespace = 'items';
                $this->limit = 0;
                $this->offset = -1;
            }
        };

        $this->assertSame(
            ['namespace' => 'items', 'limit' => 0, 'offset' => -1],
            $query->getBody()
        );
    }

    public function testEmptyFiltersArrayIsKept(): void
    {
        $query = new class () extends SdlQuery {
            public function __construct()
            {
                $this->namespace = 'items';
                $this->filters = [];
            }
        };

        $this->assertSame(
            ['namespace' => 'items', 'filters' => []],
            $query->getBody()
        );
    }

    public function testFluentSettersBuildCurrentDslBody(): void
    {
        $query = (new SdlQuery())
            ->setNamespace('items')
            ->setType('select')
            ->setLimit(10)
            ->setOffset(5)
            ->setDistinct('name')
            ->setReqTotal('cached')
            ->setFilters([['field' => 'id', 'cond' => 'EQ', 'value' => 1]])
            ->setSort(['field' => 'id', 'desc' => true])
            ->setMergeQueries([['namespace' => 'other']])
            ->setSelectFilter(['id', 'name'])
            ->setSelectFunctions(['name = snippet(<b>,</b>,5,5)'])
            ->setDropFields(['legacy'])
            ->setUpdateFields([['name' => 'rating', 'type' => 'value', 'values' => [1]]])
            ->setAggregations([['fields' => ['id'], 'type' => 'sum']])
            ->setExplain(true);

        $this->assertSame(
            [
                'namespace' => 'items',
                'type' => 'select',
                'limit' => 10,
                'offset' => 5,
                'distinct' => 'name',
                'req_total' => 'cached',
                'filters' => [['field' => 'id', 'cond' => 'EQ', 'value' => 1]],
                'sort' => ['field' => 'id', 'desc' => true],
                'merge_queries' => [['namespace' => 'other']],
                'select_filter' => ['id', 'name'],
                'select_functions' => ['name = snippet(<b>,</b>,5,5)'],
                'drop_fields' => ['legacy'],
                'update_fields' => [['name' => 'rating', 'type' => 'value', 'values' => [1]]],
                'aggregations' => [['fields' => ['id'], 'type' => 'sum']],
                'explain' => true,
            ],
            $query->getBody()
        );
    }

    public function testSettersAreFluent(): void
    {
        $query = new SdlQuery();
        $this->assertSame($query, $query->setNamespace('ns'));
        $this->assertSame($query, $query->setType('delete'));
        $this->assertSame($query, $query->setLimit(1));
        $this->assertSame($query, $query->setOffset(2));
        $this->assertSame($query, $query->setDistinct('f'));
        $this->assertSame($query, $query->setReqTotal(true));
        $this->assertSame($query, $query->setFilters([]));
        $this->assertSame($query, $query->setSort([]));
        $this->assertSame($query, $query->setMergeQueries([]));
        $this->assertSame($query, $query->setSelectFilter([]));
        $this->assertSame($query, $query->setSelectFunctions([]));
        $this->assertSame($query, $query->setDropFields([]));
        $this->assertSame($query, $query->setUpdateFields([]));
        $this->assertSame($query, $query->setAggregations([]));
        $this->assertSame($query, $query->setExplain(false));
    }

    public function testReqTotalBooleanIsNormalizedToServerEnum(): void
    {
        $this->assertSame(
            ['req_total' => 'enabled'],
            (new SdlQuery())->setReqTotal(true)->getBody()
        );
        $this->assertSame(
            ['req_total' => 'disabled'],
            (new SdlQuery())->setReqTotal(false)->getBody()
        );
    }

    public function testReqTotalStringIsPassedThrough(): void
    {
        foreach (['disabled', 'enabled', 'cached'] as $value) {
            $this->assertSame(
                ['req_total' => $value],
                (new SdlQuery())->setReqTotal($value)->getBody()
            );
        }
    }

    public function testJoinQueryInsideFiltersIsSerializedVerbatim(): void
    {
        $filter = [
            'op' => 'AND',
            'join_query' => [
                'namespace' => 'authors',
                'type' => 'INNER',
                'on' => [['left_field' => 'author_id', 'right_field' => 'id', 'cond' => 'EQ']],
            ],
        ];

        $body = (new SdlQuery())->setNamespace('items')->setFilters([$filter])->getBody();

        $this->assertSame([$filter], $body['filters']);
    }

    public function testExplainFalseIsSerialized(): void
    {
        $this->assertSame(
            ['namespace' => 'items', 'explain' => false],
            (new SdlQuery())->setNamespace('items')->setExplain(false)->getBody()
        );
    }

    public function testJoinedAndMergedAreSerialized(): void
    {
        $query = new class () extends SdlQuery {
            public function __construct()
            {
                $this->namespace = 'items';
                $this->joined = [['namespace' => 'other', 'type' => 'inner']];
                $this->merged = [['namespace' => 'third']];
            }
        };

        $body = $query->getBody();
        $this->assertSame([['namespace' => 'other', 'type' => 'inner']], $body['joined']);
        $this->assertSame([['namespace' => 'third']], $body['merged']);
    }
}
