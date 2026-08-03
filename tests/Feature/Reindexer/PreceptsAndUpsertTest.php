<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;

/**
 * Precepts (serial()/now()) and PATCH upsert against a real server.
 */
class PreceptsAndUpsertTest extends FeatureCase
{
    private string $ns = 'precepts_ns';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createNamespace($this->ns, [
            $this->intPkIndex(),
            $this->index('updated_at', FieldType::INT64, IndexType::TREE),
        ]);
    }

    private function selectAll(): array
    {
        return $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY id")
            ->getDecodedResponseBody(true)['items'] ?? [];
    }

    public function testSerialPreceptAssignsSequentialIds(): void
    {
        $items = $this->itemService($this->ns);

        $first = $items->add(['name' => 'первый'], ['id=serial()']);
        $this->assertSame(200, $first->getCode(), $first->getResponseBody());
        $second = $items->add(['name' => 'второй'], ['id=serial()']);
        $this->assertSame(200, $second->getCode(), $second->getResponseBody());

        // with precepts the server echoes the resulting items back
        $this->assertSame(1, $first->getDecodedResponseBody(true)['items'][0]['id']);
        $this->assertSame(2, $second->getDecodedResponseBody(true)['items'][0]['id']);

        $stored = $this->selectAll();
        $this->assertSame([1, 2], array_column($stored, 'id'));
        $this->assertSame(['первый', 'второй'], array_column($stored, 'name'));
    }

    public function testNowPreceptFillsTimestamp(): void
    {
        $items = $this->itemService($this->ns);

        $before = time();
        $response = $items->add(['id' => 1, 'name' => 'x'], ['updated_at=now()']);
        $this->assertSame(200, $response->getCode(), $response->getResponseBody());

        $updatedAt = $response->getDecodedResponseBody(true)['items'][0]['updated_at'];
        $this->assertGreaterThanOrEqual($before, $updatedAt);
        $this->assertLessThanOrEqual(time() + 1, $updatedAt);
    }

    public function testUpsertInsertsThenUpdates(): void
    {
        $items = $this->itemService($this->ns);

        // upsert of a missing item inserts it
        $response = $items->upsert(['id' => 5, 'name' => 'inserted']);
        $this->assertSame(200, $response->getCode(), $response->getResponseBody());
        $this->assertSame(1, $response->getDecodedResponseBody(true)['updated']);

        // upsert of an existing item updates it
        $response = $items->upsert(['id' => 5, 'name' => 'updated']);
        $this->assertSame(1, $response->getDecodedResponseBody(true)['updated']);

        $stored = $this->selectAll();
        $this->assertCount(1, $stored);
        $this->assertSame('updated', $stored[0]['name']);
    }

    public function testPreceptsInsideTransaction(): void
    {
        $txService = new \Reindexer\Services\Transaction($this->api);
        $txService->setDatabase($this->database);

        $txId = $txService->begin($this->ns)->getDecodedResponseBody(true)['tx_id'];
        $this->assertSame(200, $txService->addItem($txId, ['name' => 'a'], ['id=serial()'])->getCode());
        $this->assertSame(200, $txService->addItem($txId, ['name' => 'b'], ['id=serial()'])->getCode());
        $this->assertSame(200, $txService->commit($txId)->getCode());

        $this->assertSame([1, 2], array_column($this->selectAll(), 'id'));
    }
}
