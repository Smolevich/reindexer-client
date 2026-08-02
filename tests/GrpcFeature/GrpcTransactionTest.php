<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Grpc\ModifyMode;

/**
 * Transactions over gRPC: commit, rollback, errors inside/around a tx.
 */
#[Group('GrpcFeature')]
final class GrpcTransactionTest extends GrpcFeatureCase
{
    public function testCommitPersistsAllItems(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_txc');

        $txId = $this->client->beginTransaction($nsName);
        $this->assertGreaterThan(0, $txId);

        $this->client->addTxItems($txId, [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'c'],
        ]);
        $this->client->commitTransaction($txId);

        $this->assertSame([1, 2, 3], array_column($this->selectAll($nsName), 'id'));
    }

    public function testRollbackDiscardsAllItems(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_txr');
        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'kept']]);

        $txId = $this->client->beginTransaction($nsName);
        $this->client->addTxItems($txId, [
            ['id' => 2, 'name' => 'discarded'],
            ['id' => 3, 'name' => 'discarded'],
        ]);
        $this->client->rollbackTransaction($txId);

        $items = $this->selectAll($nsName);
        $this->assertCount(1, $items);
        $this->assertSame('kept', $items[0]['name']);
    }

    public function testDeleteInsideTransaction(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_txd');
        $this->client->modifyItems($nsName, [['id' => 1], ['id' => 2]]);

        $txId = $this->client->beginTransaction($nsName);
        $this->client->addTxItems($txId, [['id' => 1]], ModifyMode::DELETE);
        $this->client->addTxItems($txId, [['id' => 3]], ModifyMode::UPSERT);
        $this->client->commitTransaction($txId);

        $this->assertSame([2, 3], array_column($this->selectAll($nsName), 'id'));
    }

    public function testCommitUnknownTransactionThrows(): void
    {
        try {
            $this->client->commitTransaction(987654321);
            $this->fail('Expected GrpcException');
        } catch (GrpcException $e) {
            $this->assertNotSame(0, $e->getCode());
        }
    }

    public function testRollbackUnknownTransactionThrows(): void
    {
        $this->expectException(GrpcException::class);
        $this->client->rollbackTransaction(987654321);
    }

    public function testBeginTransactionOnMissingNamespaceThrows(): void
    {
        try {
            $this->client->beginTransaction('grpc_tx_missing_' . uniqid());
            $this->fail('Expected GrpcException');
        } catch (GrpcException $e) {
            $this->assertNotSame(0, $e->getCode());
            $this->assertStringContainsString('not exist', $e->getMessage());
        }
    }

    public function testAddItemsToUnknownTransactionThrows(): void
    {
        $this->expectException(GrpcException::class);
        $this->client->addTxItems(987654321, [['id' => 1]]);
    }

    public function testCommittedTransactionCannotBeReused(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_txreuse');

        $txId = $this->client->beginTransaction($nsName);
        $this->client->addTxItems($txId, [['id' => 1]]);
        $this->client->commitTransaction($txId);

        $this->expectException(GrpcException::class);
        $this->client->addTxItems($txId, [['id' => 2]]);
    }
}
