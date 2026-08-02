<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use Reindexer\Entities\Index;
use Reindexer\Enum\CollateMode;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;

/**
 * Every index type the client can express, created against a real server and
 * verified back through GET /indexes plus actual queries.
 */
class IndexTypesTest extends FeatureCase
{
    private string $ns = 'index_types_ns';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createNamespace($this->ns, [$this->intPkIndex()]);
    }

    public function testHashIndex(): void
    {
        $response = $this->indexService->create(
            $this->index('code', FieldType::STRING, IndexType::HASH),
            $this->database,
            $this->ns
        );
        $this->assertSame(200, $response->getCode());

        $this->assertIndexOnServer('code', 'hash', 'string');
    }

    public function testTreeIndexSupportsRangeAndOrder(): void
    {
        $this->indexService->create(
            $this->index('rating', FieldType::INT, IndexType::TREE),
            $this->database,
            $this->ns
        );
        $this->assertIndexOnServer('rating', 'tree', 'int');

        $items = $this->itemService($this->ns);
        foreach ([10, 50, 30, 20, 40] as $i => $rating) {
            $items->add(['id' => $i + 1, 'rating' => $rating]);
        }

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE rating > 20 ORDER BY rating DESC")
            ->getDecodedResponseBody(true);

        $this->assertSame([50, 40, 30], array_column($found['items'], 'rating'));
    }

    public function testTreeIndexOnDoubleField(): void
    {
        $this->indexService->create(
            $this->index('price', FieldType::DOUBLE, IndexType::TREE),
            $this->database,
            $this->ns
        );
        $this->assertIndexOnServer('price', 'tree', 'double');

        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'price' => 9.99]);
        $items->add(['id' => 2, 'price' => 10.01]);

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE price > 10.0")
            ->getDecodedResponseBody(true);

        $this->assertCount(1, $found['items']);
        $this->assertSame(2, $found['items'][0]['id']);
    }

    public function testColumnIndex(): void
    {
        $response = $this->indexService->create(
            $this->index('views', FieldType::INT64, IndexType::COLUMN),
            $this->database,
            $this->ns
        );
        $this->assertSame(200, $response->getCode());

        $this->assertIndexOnServer('views', '-', 'int64');
    }

    public function testBoolIndex(): void
    {
        $this->indexService->create(
            $this->index('active', FieldType::BOOL, IndexType::COLUMN),
            $this->database,
            $this->ns
        );

        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'active' => true]);
        $items->add(['id' => 2, 'active' => false]);

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE active = true")
            ->getDecodedResponseBody(true);

        $this->assertSame([1], array_column($found['items'], 'id'));
    }

    public function testArrayIndexMatchesSingleElement(): void
    {
        $index = $this->index('tags', FieldType::STRING, IndexType::HASH)->setIsArray(true);
        $response = $this->indexService->create($index, $this->database, $this->ns);
        $this->assertSame(200, $response->getCode());

        $onServer = $this->findIndexDefinition('tags');
        $this->assertTrue($onServer['is_array']);

        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'tags' => ['php', 'database']]);
        $items->add(['id' => 2, 'tags' => ['go']]);
        $items->add(['id' => 3, 'tags' => []]);

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE tags = 'php'")
            ->getDecodedResponseBody(true);

        $this->assertSame([1], array_column($found['items'], 'id'));
    }

    public function testFulltextIndexWithRealSearch(): void
    {
        $index = $this->index('body', FieldType::STRING, IndexType::TEXT);
        $response = $this->indexService->create($index, $this->database, $this->ns);
        $this->assertSame(200, $response->getCode());
        $this->assertIndexOnServer('body', 'text', 'string');

        $items = $this->itemService($this->ns);
        $items->add(['id' => 1, 'body' => 'быстрая база данных reindexer для веба']);
        $items->add(['id' => 2, 'body' => 'полнотекстовый поиск работает отлично']);
        $items->add(['id' => 3, 'body' => 'ничего интересного здесь нет']);

        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE body = 'поиск'")
            ->getDecodedResponseBody(true);
        $this->assertSame([2], array_column($found['items'], 'id'));

        // morphology/stemming: query word in different form
        $found = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->ns} WHERE body = 'базы'")
            ->getDecodedResponseBody(true);
        $this->assertSame([1], array_column($found['items'], 'id'));
    }

    public function testCollateModesAreAccepted(): void
    {
        foreach ([CollateMode::ASCII, CollateMode::UTF8, CollateMode::NUMERIC] as $i => $mode) {
            $index = $this->index('collate_' . $mode->value, FieldType::STRING, IndexType::HASH)
                ->setCollateMode($mode);
            $response = $this->indexService->create($index, $this->database, $this->ns);
            $this->assertSame(200, $response->getCode(), 'collate mode ' . $mode->value . ' rejected');

            $onServer = $this->findIndexDefinition('collate_' . $mode->value);
            $this->assertSame($mode->value, $onServer['collate_mode']);
        }
    }

    public function testDeleteIndex(): void
    {
        $this->indexService->create(
            $this->index('to_remove', FieldType::STRING, IndexType::HASH),
            $this->database,
            $this->ns
        );
        $this->assertNotNull($this->findIndexDefinition('to_remove'));

        $response = $this->indexService->delete($this->database, $this->ns, 'to_remove');
        $this->assertSame(200, $response->getCode());

        $this->assertNull($this->findIndexDefinition('to_remove'));
    }

    public function testDuplicateIndexCreationFails(): void
    {
        $index = $this->index('dup', FieldType::STRING, IndexType::HASH);
        $this->assertSame(200, $this->indexService->create($index, $this->database, $this->ns)->getCode());

        $conflicting = $this->index('dup', FieldType::INT, IndexType::TREE);
        $response = $this->indexService->create($conflicting, $this->database, $this->ns);

        $this->assertGreaterThanOrEqual(400, $response->getCode());
        $body = $response->getDecodedResponseBody(true);
        $this->assertFalse($body['success']);
        $this->assertNotSame('', $body['description']);
    }

    private function assertIndexOnServer(string $name, string $indexType, string $fieldType): void
    {
        $definition = $this->findIndexDefinition($name);
        $this->assertNotNull($definition, "index {$name} not found on server");
        $this->assertSame($indexType, $definition['index_type']);
        $this->assertSame($fieldType, $definition['field_type']);
    }

    private function findIndexDefinition(string $name): ?array
    {
        $body = $this->indexService->get($this->database, $this->ns)->getDecodedResponseBody(true);
        foreach ($body['items'] ?? [] as $item) {
            if ($item['name'] === $name) {
                return $item;
            }
        }

        return null;
    }
}
