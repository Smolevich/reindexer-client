<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;

/**
 * Server-streaming behaviour on large result sets.
 */
#[Group('GrpcFeature')]
final class GrpcStreamingTest extends GrpcFeatureCase
{
    private const ITEMS_COUNT = 600;

    public function testLargeSelectStreamsAllItems(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_stream');

        $items = [];
        for ($i = 1; $i <= self::ITEMS_COUNT; $i++) {
            $items[] = [
                'id' => $i,
                'name' => 'item_' . $i,
                // fat payload to force multiple response chunks
                'payload' => str_repeat('x', 512) . '_' . $i,
            ];
        }
        $this->client->modifyItems($nsName, $items);

        $fetched = $this->selectAll($nsName);

        $this->assertCount(self::ITEMS_COUNT, $fetched);
        $this->assertSame(1, $fetched[0]['id']);
        $this->assertSame(self::ITEMS_COUNT, $fetched[self::ITEMS_COUNT - 1]['id']);
        $this->assertSame('item_300', $fetched[299]['name']);
        $this->assertStringEndsWith('_600', $fetched[599]['payload']);
    }

    public function testStreamingIsLazyAndCanBeConsumedPartially(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_lazy');

        $items = [];
        for ($i = 1; $i <= 100; $i++) {
            $items[] = ['id' => $i, 'name' => 'n' . $i];
        }
        $this->client->modifyItems($nsName, $items);

        $consumed = [];
        foreach ($this->client->execSql(sprintf('SELECT * FROM %s ORDER BY id', $nsName)) as $item) {
            $consumed[] = $item['id'];
            if (count($consumed) === 10) {
                break; // abandon the stream mid-way: must not fatal
            }
        }

        $this->assertSame(range(1, 10), $consumed);

        // the client must still be usable after an abandoned stream
        $this->assertCount(100, $this->selectAll($nsName));
    }

    public function testSelectWithFiltersAndLimitOnLargeSet(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_filter');

        $items = [];
        for ($i = 1; $i <= 500; $i++) {
            $items[] = ['id' => $i, 'rank' => $i % 10];
        }
        $this->client->modifyItems($nsName, $items);

        $found = iterator_to_array($this->client->select([
            'namespace' => $nsName,
            'filters' => [['field' => 'id', 'cond' => 'GT', 'value' => 490]],
            'sort' => ['field' => 'id', 'desc' => false],
        ]), false);

        $this->assertSame(range(491, 500), array_column($found, 'id'));
    }

    public function testUnicodePayloadRoundTripOverGrpc(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_unicode');

        $this->client->modifyItems($nsName, [
            ['id' => 1, 'name' => 'кириллица-🚀-汉字', 'note' => "line1\nline2\t\"quoted\""],
        ]);

        $fetched = $this->selectAll($nsName);

        $this->assertSame('кириллица-🚀-汉字', $fetched[0]['name']);
        $this->assertSame("line1\nline2\t\"quoted\"", $fetched[0]['note']);
    }
}
