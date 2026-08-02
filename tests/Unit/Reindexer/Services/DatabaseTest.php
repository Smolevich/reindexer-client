<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\BaseService;
use Reindexer\Services\Database;
use Tests\Unit\Reindexer\Support\RecordingApi;

#[CoversClass(Database::class)]
#[CoversClass(BaseService::class)]
class DatabaseTest extends TestCase
{
    private RecordingApi $api;
    private Database $service;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
        $this->service = new Database($this->api);
    }

    public function testCreateSendsPostWithJsonName(): void
    {
        $this->service->create('mydb');

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db', $call['uri']);
        $this->assertSame('{"name":"mydb"}', $call['body']);
        $this->assertSame('application/json;charset=utf-8', $call['headers']['Content-Type']);
    }

    public function testCreateSendsUnicodeNameUnescaped(): void
    {
        $this->service->create('база');

        // reindexer 5.x mangles \uXXXX surrogate escapes, so the client
        // must send raw UTF-8 (JSON_UNESCAPED_UNICODE)
        $this->assertSame('{"name":"база"}', $this->api->lastCall()['body']);
    }

    public function testGetListSendsGetWithoutBody(): void
    {
        $this->service->getList();

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testDropSendsDeleteWithNameInUri(): void
    {
        $this->service->drop('mydb');

        $call = $this->api->lastCall();
        $this->assertSame('DELETE', $call['method']);
        $this->assertSame('/api/v1/db/mydb', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testSetVersionChangesUriPrefix(): void
    {
        $this->service->setVersion('v2');
        $this->service->getList();

        $this->assertSame('/api/v2/db', $this->api->lastCall()['uri']);
    }

    public function testSetVersionIsFluent(): void
    {
        $this->assertSame($this->service, $this->service->setVersion('v3'));
    }

    public function testSetHeadersReplacesDefaults(): void
    {
        $this->service->setHeaders(['X-Custom' => 'yes']);
        $this->service->getList();

        $this->assertSame(['X-Custom' => 'yes'], $this->api->lastCall()['headers']);
    }

    public function testAddHeadersMergesWithDefaults(): void
    {
        $this->service->addHeaders(['X-Custom' => 'yes']);
        $this->service->getList();

        $this->assertSame(
            [
                'Content-Type' => 'application/json;charset=utf-8',
                'X-Custom' => 'yes',
            ],
            $this->api->lastCall()['headers']
        );
    }

    public function testAddHeadersOverridesExistingKey(): void
    {
        $this->service->addHeaders(['Content-Type' => 'text/plain']);
        $this->service->getList();

        $this->assertSame(['Content-Type' => 'text/plain'], $this->api->lastCall()['headers']);
    }

    public function testResponseIsPassedThrough(): void
    {
        $this->api->willRespond('{"items":[{"name":"db1"}],"total_items":1}');
        $response = $this->service->getList();

        $this->assertSame(200, $response->getCode());
        $this->assertSame(
            ['items' => [['name' => 'db1']], 'total_items' => 1],
            $response->getDecodedResponseBody(true)
        );
    }
}
