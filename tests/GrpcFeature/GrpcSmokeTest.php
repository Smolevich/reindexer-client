<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Transport\Grpc\GrpcClient;

#[Group('GrpcFeature')]
final class GrpcSmokeTest extends TestCase
{
    private const DB_NAME = 'grpc_smoke_db';
    private string $nsName;

    private GrpcClient $client;

    protected function setUp(): void
    {
        if (!extension_loaded('grpc')) {
            $this->markTestSkipped('The "grpc" extension is not loaded.');
        }

        $target = (string) getenv('REINDEXER_GRPC_TARGET');
        if ($target === '') {
            $this->markTestSkipped('REINDEXER_GRPC_TARGET is not set.');
        }

        $this->nsName = uniqid('grpc_smoke_');
        $this->client = new GrpcClient($target);
        $this->connectCreatingDatabase();
        $this->dropNamespaceIfExists();
    }

    protected function tearDown(): void
    {
        if (isset($this->client)) {
            $this->dropNamespaceIfExists();
        }
    }

    public function testFullGrpcFlow(): void
    {
        $client = $this->client;

        $client->openNamespace($this->nsName);
        $this->assertContains($this->nsName, $client->enumNamespaces());

        $client->addIndex($this->nsName, [
            'name' => 'id',
            'fieldType' => 'int',
            'indexType' => 'hash',
            'isPk' => true,
        ]);
        $client->addIndex($this->nsName, [
            'name' => 'name',
            'fieldType' => 'string',
            'indexType' => 'hash',
        ]);

        $client->modifyItems($this->nsName, [
            ['id' => 1, 'name' => 'first'],
            ['id' => 2, 'name' => 'second'],
            ['id' => 3, 'name' => 'third'],
        ]);

        $items = iterator_to_array(
            $client->execSql(sprintf('SELECT * FROM %s ORDER BY id', $this->nsName)),
            false
        );
        $this->assertCount(3, $items);
        $this->assertSame([1, 2, 3], array_column($items, 'id'));
        $this->assertSame(['first', 'second', 'third'], array_column($items, 'name'));

        $found = iterator_to_array(
            $client->select([
                'namespace' => $this->nsName,
                'filters' => [
                    ['field' => 'id', 'cond' => 'EQ', 'value' => 2],
                ],
            ]),
            false
        );
        $this->assertCount(1, $found);
        $this->assertSame('second', $found[0]['name']);

        $transactionId = $client->beginTransaction($this->nsName);
        $client->addTxItems($transactionId, [
            ['id' => 4, 'name' => 'fourth'],
        ]);
        $client->commitTransaction($transactionId);

        $afterCommit = iterator_to_array(
            $client->execSql(sprintf('SELECT * FROM %s', $this->nsName)),
            false
        );
        $this->assertCount(4, $afterCommit);

        $transactionId = $client->beginTransaction($this->nsName);
        $client->addTxItems($transactionId, [
            ['id' => 5, 'name' => 'fifth'],
        ]);
        $client->rollbackTransaction($transactionId);

        $afterRollback = iterator_to_array(
            $client->execSql(sprintf('SELECT * FROM %s', $this->nsName)),
            false
        );
        $this->assertCount(4, $afterRollback);

        $client->dropNamespace($this->nsName);
        $this->assertNotContains($this->nsName, $client->enumNamespaces());
    }

    private function connectCreatingDatabase(): void
    {
        try {
            $this->client->connect(self::DB_NAME);
        } catch (GrpcException) {
            $this->client->createDatabase(self::DB_NAME);
            $this->client->connect(self::DB_NAME);
        }
    }

    private function dropNamespaceIfExists(): void
    {
        try {
            $this->client->dropNamespace($this->nsName);
        } catch (GrpcException) {
            // namespace does not exist, nothing to clean up
        }
    }
}
