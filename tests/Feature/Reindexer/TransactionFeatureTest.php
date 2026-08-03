<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use Reindexer\Services\Transaction;

/**
 * HTTP transactions against a real server: items become visible only after
 * commit and disappear entirely after rollback.
 */
class TransactionFeatureTest extends FeatureCase
{
    private string $ns = 'tx_ns';
    private Transaction $txService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createNamespace($this->ns, [$this->intPkIndex()]);
        $this->txService = new Transaction($this->api);
        $this->txService->setDatabase($this->database);
    }

    private function beginTx(): string
    {
        $response = $this->txService->begin($this->ns);
        $this->assertSame(200, $response->getCode(), $response->getResponseBody());
        $txId = $response->getDecodedResponseBody(true)['tx_id'] ?? '';
        $this->assertNotSame('', $txId);

        return $txId;
    }

    private function itemIds(): array
    {
        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY id")
            ->getDecodedResponseBody(true);

        return array_column($found['items'], 'id');
    }

    public function testCommitAppliesAllItemOperationsAtomically(): void
    {
        $txId = $this->beginTx();

        $this->assertSame(200, $this->txService->addItem($txId, ['id' => 1, 'name' => 'раз'])->getCode());
        $this->assertSame(200, $this->txService->addItem($txId, ['id' => 2, 'name' => 'два'])->getCode());
        $this->assertSame(200, $this->txService->upsertItem($txId, ['id' => 2, 'name' => 'два-обновлён'])->getCode());

        // nothing is visible until commit
        $this->assertSame([], $this->itemIds());

        $commit = $this->txService->commit($txId);
        $this->assertSame(200, $commit->getCode(), $commit->getResponseBody());

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY id")
            ->getDecodedResponseBody(true);
        $this->assertSame([1, 2], array_column($found['items'], 'id'));
        $this->assertSame('два-обновлён', $found['items'][1]['name']);
    }

    public function testRollbackDiscardsAllOperations(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 10, 'name' => 'до транзакции']);

        $txId = $this->beginTx();
        $this->assertSame(200, $this->txService->addItem($txId, ['id' => 11, 'name' => 'внутри'])->getCode());
        $this->assertSame(200, $this->txService->deleteItem($txId, ['id' => 10])->getCode());

        $rollback = $this->txService->rollback($txId);
        $this->assertSame(200, $rollback->getCode(), $rollback->getResponseBody());

        $this->assertSame([10], $this->itemIds(), 'rollback must leave pre-transaction data untouched');
    }

    public function testUpdateAndDeleteItemsInsideTransaction(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'name' => 'old', 'rating' => 1]);
        $items->add(['id' => 2, 'name' => 'gone']);

        $txId = $this->beginTx();
        $this->assertSame(200, $this->txService->updateItem($txId, ['id' => 1, 'name' => 'new', 'rating' => 5])->getCode());
        $this->assertSame(200, $this->txService->deleteItem($txId, ['id' => 2])->getCode());
        $this->assertSame(200, $this->txService->commit($txId)->getCode());

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY id")
            ->getDecodedResponseBody(true);
        $this->assertCount(1, $found['items']);
        $this->assertSame('new', $found['items'][0]['name']);
        $this->assertSame(5, $found['items'][0]['rating']);
    }

    public function testSqlUpdateQueryInsideTransaction(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'rating' => 1]);
        $items->add(['id' => 2, 'rating' => 1]);

        $txId = $this->beginTx();
        $response = $this->txService->sqlQuery($txId, "UPDATE {$this->ns} SET rating = 9 WHERE id = 2");
        $this->assertSame(200, $response->getCode(), $response->getResponseBody());
        $this->assertSame(200, $this->txService->commit($txId)->getCode());

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY id")
            ->getDecodedResponseBody(true);
        $this->assertSame([1, 9], array_column($found['items'], 'rating'));
    }

    public function testDeleteQueryDslInsideTransaction(): void
    {
        $items = $this->itemService($this->ns);
        foreach ([1, 2, 3] as $id) {
            $items->add(['id' => $id]);
        }

        $txId = $this->beginTx();
        $response = $this->txService->deleteQuery($txId, [
            'namespace' => $this->ns,
            'filters' => [['field' => 'id', 'cond' => 'GT', 'value' => 1]],
        ]);
        $this->assertSame(200, $response->getCode(), $response->getResponseBody());
        $this->assertSame(200, $this->txService->commit($txId)->getCode());

        $this->assertSame([1], $this->itemIds());
    }

    public function testCommitOfUnknownTransactionFails(): void
    {
        $response = $this->txService->commit('unknown_tx_id');

        $this->assertGreaterThanOrEqual(400, $response->getCode());
        $body = $response->getDecodedResponseBody(true);
        $this->assertFalse($body['success']);
    }
}
