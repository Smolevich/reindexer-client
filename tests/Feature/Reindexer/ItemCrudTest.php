<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;

/**
 * Full CRUD cycle for items over HTTP against a real server.
 */
class ItemCrudTest extends FeatureCase
{
    private string $ns = 'crud_ns';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createNamespace($this->ns, [
            $this->intPkIndex(),
            $this->index('name', FieldType::STRING, IndexType::HASH),
            $this->index('rating', FieldType::INT, IndexType::TREE),
        ]);
    }

    private function selectAll(): array
    {
        return $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} ORDER BY id")
            ->getDecodedResponseBody(true)['items'];
    }

    public function testInsertReadUpdateDelete(): void
    {
        $items = $this->itemService($this->ns);

        // create
        $response = $items->add(['id' => 1, 'name' => 'first', 'rating' => 10]);
        $this->assertSame(200, $response->getCode());
        $this->assertSame(1, $response->getDecodedResponseBody(true)['updated']);

        // read
        $this->assertSame([['id' => 1, 'name' => 'first', 'rating' => 10]], $this->selectAll());

        // update (PUT)
        $response = $items->update(['id' => 1, 'name' => 'renamed', 'rating' => 99]);
        $this->assertSame(1, $response->getDecodedResponseBody(true)['updated']);
        $this->assertSame('renamed', $this->selectAll()[0]['name']);

        // delete
        $response = $items->delete(['id' => 1]);
        $this->assertSame(1, $response->getDecodedResponseBody(true)['updated']);
        $this->assertSame([], $this->selectAll());
    }

    public function testDuplicatePkInsertIsIgnored(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'name' => 'original', 'rating' => 1]);

        // POST is insert: second insert with the same PK must not overwrite
        $response = $items->add(['id' => 1, 'name' => 'imposter', 'rating' => 2]);

        $this->assertSame(200, $response->getCode());
        $this->assertSame(0, $response->getDecodedResponseBody(true)['updated']);
        $this->assertSame('original', $this->selectAll()[0]['name']);
    }

    public function testUpdateOfMissingItemDoesNotUpsert(): void
    {
        $items = $this->itemService($this->ns);

        // PUT /items only updates existing items (no upsert semantics)
        $response = $items->update(['id' => 5, 'name' => 'ghost', 'rating' => 5]);

        $this->assertSame(200, $response->getCode());
        $this->assertSame(0, $response->getDecodedResponseBody(true)['updated']);
        $this->assertSame([], $this->selectAll());
    }

    public function testDeleteMissingItemReportsZeroUpdated(): void
    {
        $items = $this->itemService($this->ns);

        $response = $items->delete(['id' => 12345]);

        $this->assertSame(0, $response->getDecodedResponseBody(true)['updated'] ?? 0);
        $this->assertSame([], $this->selectAll());
    }

    public function testGetWithLimitOffsetAndSort(): void
    {
        $items = $this->itemService($this->ns);
        foreach ([['id' => 1, 'rating' => 30], ['id' => 2, 'rating' => 10], ['id' => 3, 'rating' => 50], ['id' => 4, 'rating' => 20]] as $item) {
            $items->add($item + ['name' => 'n' . $item['id']]);
        }

        $body = $items->get(2, 1, 'rating', 'desc')->getDecodedResponseBody(true);

        // rating desc: 50, 30, 20, 10 -> offset 1, limit 2 -> 30, 20
        $this->assertSame([30, 20], array_column($body['items'], 'rating'));
        $this->assertSame(4, $body['total_items']);
    }

    public function testUnicodeAndSpecialCharactersRoundTrip(): void
    {
        $items = $this->itemService($this->ns);
        $payload = [
            'id' => 1,
            'name' => "кириллица-🚀-汉字-\"quoted\"-<tag>&'apo'",
            'rating' => 1,
            'nested' => ['path' => 'a/b\\c', 'newline' => "line1\nline2"],
        ];

        $this->assertSame(200, $items->add($payload)->getCode());

        $stored = $this->selectAll()[0];
        $this->assertSame($payload['name'], $stored['name']);
        $this->assertSame($payload['nested'], $stored['nested']);
    }

    public function testTruncateEmptiesNamespaceButKeepsIt(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'name' => 'x', 'rating' => 1]);
        $items->add(['id' => 2, 'name' => 'y', 'rating' => 2]);
        $this->assertCount(2, $this->selectAll());

        $response = $this->nsService->truncate($this->ns);
        $this->assertSame(200, $response->getCode());

        $this->assertSame([], $this->selectAll());
        // namespace still exists and accepts writes
        $this->assertSame(200, $items->add(['id' => 3, 'name' => 'z', 'rating' => 3])->getCode());
        $this->assertCount(1, $this->selectAll());
    }

    public function testDropNamespaceRemovesIt(): void
    {
        $response = $this->nsService->drop($this->ns);
        $this->assertSame(200, $response->getCode());

        $response = $this->nsService->get($this->ns);
        $this->assertSame(404, $response->getCode());
    }

    public function testRenameNamespace(): void
    {
        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'name' => 'kept', 'rating' => 1]);

        $response = $this->nsService->rename($this->ns, 'renamed_ns');
        $this->assertSame(200, $response->getCode());

        $found = $this->queryService
            ->createByHttpGet('SELECT * FROM renamed_ns')
            ->getDecodedResponseBody(true);
        $this->assertSame('kept', $found['items'][0]['name']);

        $this->assertSame(404, $this->nsService->get($this->ns)->getCode());
    }

    public function testNamespaceListContainsCreatedNamespace(): void
    {
        $body = $this->nsService->getList()->getDecodedResponseBody(true);

        $this->assertContains($this->ns, array_column($body['items'], 'name'));
    }
}
