<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Services\Item;
use Tests\Unit\Reindexer\Support\RecordingApi;

#[CoversClass(Item::class)]
class ItemTest extends TestCase
{
    private RecordingApi $api;
    private Item $service;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
        $this->service = new Item($this->api);
        $this->service->setDatabase('db');
        $this->service->setNamespace('ns');
    }

    public function testDatabaseAndNamespaceAccessors(): void
    {
        $service = new Item($this->api);
        $this->assertSame('', $service->getDatabase());
        $this->assertSame('', $service->getNamespace());

        $service->setDatabase('mydb');
        $service->setNamespace('myns');
        $this->assertSame('mydb', $service->getDatabase());
        $this->assertSame('myns', $service->getNamespace());
    }

    public function testAddSendsPostWithJsonBody(): void
    {
        $this->service->add(['id' => 1, 'name' => 'первый']);

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/items', $call['uri']);
        $this->assertSame(['id' => 1, 'name' => 'первый'], json_decode($call['body'], true));
    }

    public function testUpdateSendsPut(): void
    {
        $this->service->update(['id' => 1, 'name' => 'updated']);

        $call = $this->api->lastCall();
        $this->assertSame('PUT', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/items', $call['uri']);
        $this->assertSame(['id' => 1, 'name' => 'updated'], json_decode($call['body'], true));
    }

    public function testDeleteSendsDeleteWithBody(): void
    {
        $this->service->delete(['id' => 1]);

        $call = $this->api->lastCall();
        $this->assertSame('DELETE', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/items', $call['uri']);
        $this->assertSame(['id' => 1], json_decode($call['body'], true));
    }

    public function testUpsertSendsPatch(): void
    {
        $this->service->upsert(['id' => 1, 'name' => 'upserted']);

        $call = $this->api->lastCall();
        $this->assertSame('PATCH', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/items', $call['uri']);
        $this->assertSame(['id' => 1, 'name' => 'upserted'], json_decode($call['body'], true));
    }

    /**
     * @param callable(Item): \Reindexer\Response $invoker
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('writeMethodProvider')]
    public function testWriteMethodsAppendExplodedPrecepts(callable $invoker, string $httpMethod): void
    {
        $invoker($this->service);

        $call = $this->api->lastCall();
        $this->assertSame($httpMethod, $call['method']);
        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/items?precepts=id%3Dserial%28%29&precepts=updated_at%3Dnow%28%29',
            $call['uri']
        );
    }

    public static function writeMethodProvider(): array
    {
        $precepts = ['id=serial()', 'updated_at=now()'];

        return [
            'add' => [static fn (Item $s) => $s->add(['name' => 'x'], $precepts), 'POST'],
            'update' => [static fn (Item $s) => $s->update(['id' => 1], $precepts), 'PUT'],
            'upsert' => [static fn (Item $s) => $s->upsert(['id' => 1], $precepts), 'PATCH'],
            'delete' => [static fn (Item $s) => $s->delete(['id' => 1], $precepts), 'DELETE'],
        ];
    }

    public function testSinglePreceptProducesSinglePair(): void
    {
        $this->service->add(['name' => 'x'], ['id=serial()']);

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/items?precepts=id%3Dserial%28%29',
            $this->api->lastCall()['uri']
        );
    }

    public function testEmptyPreceptsAddNoQueryString(): void
    {
        $this->service->add(['id' => 1], []);

        $this->assertSame('/api/v1/db/db/namespaces/ns/items', $this->api->lastCall()['uri']);
    }

    public function testEmptyPayloadSerializesToEmptyJsonArray(): void
    {
        $this->service->add();

        $this->assertSame('[]', $this->api->lastCall()['body']);
    }

    public function testGetWithoutParamsHasNoQueryString(): void
    {
        $this->service->get();

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/items', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testGetWithAllParamsBuildsQueryString(): void
    {
        $this->service->get(10, 20, 'name', 'desc');

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/items?limit=10&offset=20&sort_field=name&sort_order=desc',
            $this->api->lastCall()['uri']
        );
    }

    public function testGetWithZeroLimitAndOffsetOmitsThem(): void
    {
        $this->service->get(0, 0, 'name');

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/items?sort_field=name',
            $this->api->lastCall()['uri']
        );
    }

    public function testGetWithOnlyLimit(): void
    {
        $this->service->get(5);

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/items?limit=5',
            $this->api->lastCall()['uri']
        );
    }

    public function testGetWithOnlySortOrder(): void
    {
        $this->service->get(0, 0, '', 'asc');

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/items?sort_order=asc',
            $this->api->lastCall()['uri']
        );
    }

    public function testUnicodePayloadIsNotMangled(): void
    {
        $this->service->add(['title' => 'Привет 🌍', 'tags' => ['汉字', 'ダメ']]);

        $decoded = json_decode($this->api->lastCall()['body'], true);
        $this->assertSame('Привет 🌍', $decoded['title']);
        $this->assertSame(['汉字', 'ダメ'], $decoded['tags']);
    }
}
