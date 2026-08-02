<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Grpc\ModifyMode;

/**
 * ModifyItem bidirectional streaming: all modify modes, raw JSON input,
 * server-side errors.
 */
#[Group('GrpcFeature')]
final class GrpcModifyItemTest extends GrpcFeatureCase
{
    public function testUpsertInsertsAndOverwrites(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_upsert');

        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'v1']]);
        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'v2']], ModifyMode::UPSERT);

        $items = $this->selectAll($nsName);
        $this->assertCount(1, $items);
        $this->assertSame('v2', $items[0]['name']);
    }

    public function testInsertModeDoesNotOverwriteExisting(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_insert');

        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'original']], ModifyMode::INSERT);
        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'imposter']], ModifyMode::INSERT);

        $items = $this->selectAll($nsName);
        $this->assertCount(1, $items);
        $this->assertSame('original', $items[0]['name']);
    }

    public function testUpdateModeOnlyTouchesExisting(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_update');

        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'existing']]);
        $this->client->modifyItems($nsName, [
            ['id' => 1, 'name' => 'updated'],
            ['id' => 2, 'name' => 'ghost'],
        ], ModifyMode::UPDATE);

        $items = $this->selectAll($nsName);
        $this->assertCount(1, $items, 'UPDATE must not insert new items');
        $this->assertSame('updated', $items[0]['name']);
    }

    public function testDeleteMode(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_delete');

        $this->client->modifyItems($nsName, [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'c'],
        ]);
        $this->client->modifyItems($nsName, [['id' => 2]], ModifyMode::DELETE);

        $this->assertSame([1, 3], array_column($this->selectAll($nsName), 'id'));
    }

    public function testRawJsonStringItemsAreAccepted(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_raw');

        $this->client->modifyItems($nsName, [
            '{"id":1,"name":"from-raw-json"}',
            ['id' => 2, 'name' => 'from-array'],
        ]);

        $items = $this->selectAll($nsName);
        $this->assertSame(['from-raw-json', 'from-array'], array_column($items, 'name'));
    }

    public function testModifyIntoMissingNamespaceThrows(): void
    {
        $this->expectException(GrpcException::class);
        $this->client->modifyItems('grpc_missing_ns_' . uniqid(), [['id' => 1]]);
    }

    public function testInvalidJsonItemThrows(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_badjson');

        $this->expectException(GrpcException::class);
        $this->client->modifyItems($nsName, ['{broken json']);
    }

    public function testGrpcUpdateQueryDsl(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_dslupd');
        $this->client->modifyItems($nsName, [
            ['id' => 1, 'status' => 'old'],
            ['id' => 2, 'status' => 'old'],
        ]);

        $updated = iterator_to_array($this->client->update([
            'namespace' => $nsName,
            'filters' => [['field' => 'id', 'cond' => 'EQ', 'value' => 1]],
            'update_fields' => [['name' => 'status', 'values' => ['new']]],
        ]), false);

        $this->assertNotEmpty($updated);
        $items = $this->selectAll($nsName);
        $this->assertSame('new', $items[0]['status']);
        $this->assertSame('old', $items[1]['status']);
    }

    public function testGrpcDeleteQueryDsl(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_dsldel');
        $this->client->modifyItems($nsName, [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        iterator_to_array($this->client->delete([
            'namespace' => $nsName,
            'filters' => [['field' => 'id', 'cond' => 'LT', 'value' => 3]],
        ]), false);

        $this->assertSame([3], array_column($this->selectAll($nsName), 'id'));
    }

    public function testTruncateNamespaceOverGrpc(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_trunc');
        $this->client->modifyItems($nsName, [['id' => 1], ['id' => 2]]);
        $this->assertCount(2, $this->selectAll($nsName));

        $this->client->truncateNamespace($nsName);

        $this->assertSame([], $this->selectAll($nsName));
        // namespace still usable
        $this->client->modifyItems($nsName, [['id' => 9]]);
        $this->assertCount(1, $this->selectAll($nsName));
    }
}
