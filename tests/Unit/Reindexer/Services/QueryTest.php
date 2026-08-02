<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Services\Query;
use Tests\Unit\Reindexer\Support\RecordingApi;

#[CoversClass(Query::class)]
class QueryTest extends TestCase
{
    private RecordingApi $api;
    private Query $service;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
        $this->service = new Query($this->api);
        $this->service->setDatabase('db');
    }

    public function testDatabaseAccessors(): void
    {
        $service = new Query($this->api);
        $this->assertSame('', $service->getDatabase());

        $service->setDatabase('mydb');
        $this->assertSame('mydb', $service->getDatabase());
    }

    public function testCreateByHttpGetUrlencodesQuery(): void
    {
        $this->service->createByHttpGet("SELECT * FROM ns WHERE name = 'значение'");

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame(
            '/api/v1/db/db/query?q=' . urlencode("SELECT * FROM ns WHERE name = 'значение'"),
            $call['uri']
        );
        $this->assertNull($call['body']);
    }

    public function testCreateByHttpGetEncodesSpecialCharacters(): void
    {
        $this->service->createByHttpGet('SELECT * FROM ns WHERE a = "b&c=d" AND e > 5');

        $uri = $this->api->lastCall()['uri'];
        $this->assertStringNotContainsString(' ', $uri);
        $this->assertStringNotContainsString('&c=d', $uri, 'ampersand inside the query must be encoded');
        $this->assertStringContainsString('%26c%3Dd', $uri);
    }

    public function testCreateSqlQueryByHttpPostSendsRawBody(): void
    {
        $sql = 'SELECT * FROM ns WHERE id = 1';
        $this->service->createSqlQueryByHttpPost($sql);

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/sqlquery', $call['uri']);
        $this->assertSame($sql, $call['body'], 'SQL must be passed as-is, not JSON encoded');
    }

    public function testCreateSdlQueryByHttpPostPassesRawJsonStringThrough(): void
    {
        $query = '{"namespace":"ns"}';
        $this->service->createSdlQueryByHttpPost($query);

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/query', $call['uri']);
        $this->assertSame($query, $call['body'], 'raw JSON must not be double-encoded (used to 500 on the server)');
    }

    public function testCreateSdlQueryByHttpPostEncodesArrayDsl(): void
    {
        $this->service->createSdlQueryByHttpPost([
            'namespace' => 'ns',
            'limit' => 2,
            'filters' => [['field' => 'rating', 'cond' => 'GT', 'value' => 10]],
        ]);

        $this->assertSame(
            '{"namespace":"ns","limit":2,"filters":[{"field":"rating","cond":"GT","value":10}]}',
            $this->api->lastCall()['body']
        );
    }

    public function testVersionOverrideIsRespected(): void
    {
        $this->service->setVersion('v9');
        $this->service->createByHttpGet('SELECT 1');

        $this->assertStringStartsWith('/api/v9/db/db/query?q=', $this->api->lastCall()['uri']);
    }
}
