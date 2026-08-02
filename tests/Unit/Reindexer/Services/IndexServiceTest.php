<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Services\Index;
use Tests\Unit\Reindexer\Support\RecordingApi;

#[CoversClass(Index::class)]
class IndexServiceTest extends TestCase
{
    private RecordingApi $api;
    private Index $service;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
        $this->service = new Index($this->api);
    }

    private function makeEntity(): IndexEntity
    {
        return (new IndexEntity())
            ->setName('id')
            ->setJsonPaths(['id'])
            ->setFieldType(FieldType::INT)
            ->setIndexType(IndexType::HASH)
            ->setIsPk(true);
    }

    public function testCreatePostsSerializedEntity(): void
    {
        $this->service->create($this->makeEntity(), 'db', 'ns');

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/indexes', $call['uri']);
        $this->assertSame(
            [
                'name' => 'id',
                'json_paths' => ['id'],
                'field_type' => 'int',
                'index_type' => 'hash',
                'is_pk' => true,
                'is_array' => false,
                'is_dense' => false,
                'is_appendable' => false,
                'collate_mode' => 'none',
            ],
            json_decode($call['body'], true)
        );
    }

    public function testGetBuildsUriFromDatabaseAndNamespace(): void
    {
        $this->service->get('mydb', 'myns');

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame('/api/v1/db/mydb/namespaces/myns/indexes', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testDeleteAppendsIndexNameToUri(): void
    {
        $this->service->delete('mydb', 'myns', 'my_index');

        $call = $this->api->lastCall();
        $this->assertSame('DELETE', $call['method']);
        $this->assertSame('/api/v1/db/mydb/namespaces/myns/indexes/my_index', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testVersionOverrideIsRespected(): void
    {
        $this->service->setVersion('beta');
        $this->service->get('db', 'ns');

        $this->assertSame('/api/beta/db/db/namespaces/ns/indexes', $this->api->lastCall()['uri']);
    }
}
