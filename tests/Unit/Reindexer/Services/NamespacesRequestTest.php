<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Services\Namespaces;
use Tests\Unit\Reindexer\Support\RecordingApi;

/**
 * Verifies the exact HTTP requests (method, URI, body) built by Namespaces.
 */
#[CoversClass(Namespaces::class)]
class NamespacesRequestTest extends TestCase
{
    private RecordingApi $api;
    private Namespaces $service;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
        $this->service = new Namespaces($this->api);
        $this->service->setDatabase('db');
    }

    public function testGetListDefaultsToAscSortOrder(): void
    {
        $this->service->getList();

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces?sort_order=asc', $call['uri']);
    }

    public function testGetListWithEmptySortOrderHasNoQueryString(): void
    {
        $this->service->getList('');

        $this->assertSame('/api/v1/db/db/namespaces', $this->api->lastCall()['uri']);
    }

    public function testGetListWithDescSortOrder(): void
    {
        $this->service->getList('desc');

        $this->assertSame('/api/v1/db/db/namespaces?sort_order=desc', $this->api->lastCall()['uri']);
    }

    public function testCreateWithoutIndexes(): void
    {
        $this->service->create('ns');

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces', $call['uri']);
        $this->assertSame(
            ['name' => 'ns', 'storage' => ['enabled' => true], 'indexes' => []],
            json_decode($call['body'], true)
        );
    }

    public function testCreateSerializesIndexEntities(): void
    {
        $index = (new IndexEntity())
            ->setName('id')
            ->setJsonPaths(['id'])
            ->setFieldType(FieldType::INT)
            ->setIndexType(IndexType::HASH)
            ->setIsPk(true);

        $this->service->create('ns', [$index]);

        $body = json_decode($this->api->lastCall()['body'], true);
        $this->assertCount(1, $body['indexes']);
        $this->assertSame('id', $body['indexes'][0]['name']);
        $this->assertSame('int', $body['indexes'][0]['field_type']);
        $this->assertTrue($body['indexes'][0]['is_pk']);
    }

    public function testCreateSkipsNonIndexEntities(): void
    {
        $this->service->create('ns', ['bogus', 42, new \stdClass(), null]);

        $body = json_decode($this->api->lastCall()['body'], true);
        $this->assertSame([], $body['indexes']);
    }

    public function testDropBuildsDeleteRequest(): void
    {
        $this->service->drop('ns');

        $call = $this->api->lastCall();
        $this->assertSame('DELETE', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns', $call['uri']);
    }

    public function testGetBuildsGetRequest(): void
    {
        $this->service->get('ns');

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns', $call['uri']);
    }

    public function testTruncateUsesDeleteOnTruncateEndpoint(): void
    {
        $this->service->truncate('ns');

        $call = $this->api->lastCall();
        $this->assertSame('DELETE', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/truncate', $call['uri']);
    }

    public function testRenameUsesGetWithBothNames(): void
    {
        $this->service->rename('old_ns', 'new_ns');

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/old_ns/rename/new_ns', $call['uri']);
    }

    public function testGetMetaListDefaults(): void
    {
        $this->service->getMetaList('ns');

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/metalist?with_values=false', $call['uri']);
    }

    public function testGetMetaListWithAllParams(): void
    {
        $this->service->getMetaList('ns', 10, 5, 'desc', true);

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/metalist?limit=10&offset=5&with_values=true&sort_order=desc',
            $this->api->lastCall()['uri']
        );
    }

    public function testGetMetaListZeroLimitOffsetAreOmitted(): void
    {
        $this->service->getMetaList('ns', 0, 0, 'asc', false);

        $this->assertSame(
            '/api/v1/db/db/namespaces/ns/metalist?with_values=false&sort_order=asc',
            $this->api->lastCall()['uri']
        );
    }

    public function testAddMetaDataKeySendsPutWithKeyValueJson(): void
    {
        $this->service->addMetaDataKey('ns', 'meta_key', 'значение');

        $call = $this->api->lastCall();
        $this->assertSame('PUT', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/metabykey', $call['uri']);
        $this->assertSame(
            ['key' => 'meta_key', 'value' => 'значение'],
            json_decode($call['body'], true)
        );
    }

    public function testGetMetaDataKeyUsesPutWithKeyInUri(): void
    {
        $this->service->getMetaDataKey('ns', 'meta_key');

        $call = $this->api->lastCall();
        $this->assertSame('PUT', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/metabykey/meta_key', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testSchemaSendsPutWithJsonConfig(): void
    {
        $config = ['type' => 'namespaces', 'namespaces' => ['wal_size' => 5000000]];
        $this->service->schema('ns', $config);

        $call = $this->api->lastCall();
        $this->assertSame('PUT', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/schema', $call['uri']);
        $this->assertSame($config, json_decode($call['body'], true));
    }

    public function testEmptyDatabaseFallsBackToEmptyString(): void
    {
        $service = new Namespaces($this->api);
        $this->assertSame('', $service->getDatabase());

        $service->getList('');
        $this->assertSame('/api/v1/db//namespaces', $this->api->lastCall()['uri']);
    }
}
