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
