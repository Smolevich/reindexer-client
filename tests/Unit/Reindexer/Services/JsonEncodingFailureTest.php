<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Services\Database;
use Reindexer\Services\Index;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;
use Tests\Unit\Reindexer\Support\RecordingApi;

/**
 * Regression tests: every service json_encode() runs with JSON_THROW_ON_ERROR.
 * An unencodable payload (e.g. invalid UTF-8) used to turn json_encode() into
 * `false`, which then blew up inside request() instead of failing loudly.
 */
#[CoversClass(Database::class)]
#[CoversClass(Namespaces::class)]
#[CoversClass(Index::class)]
#[CoversClass(Item::class)]
#[CoversClass(Query::class)]
class JsonEncodingFailureTest extends TestCase
{
    private const INVALID_UTF8 = "\xB1\x31";

    private RecordingApi $api;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
    }

    /**
     * @param callable(RecordingApi): mixed $invoker
     */
    #[DataProvider('unencodablePayloadProvider')]
    public function testUnencodablePayloadThrowsJsonException(callable $invoker): void
    {
        $this->expectException(\JsonException::class);

        $invoker($this->api);
    }

    public static function unencodablePayloadProvider(): array
    {
        $invalid = self::INVALID_UTF8;

        $namespaces = static function (RecordingApi $api): Namespaces {
            $service = new Namespaces($api);
            $service->setDatabase('db');

            return $service;
        };

        $item = static function (RecordingApi $api): Item {
            $service = new Item($api);
            $service->setDatabase('db');
            $service->setNamespace('ns');

            return $service;
        };

        return [
            'Database::create' => [
                static fn (RecordingApi $api) => (new Database($api))->create($invalid),
            ],
            'Index::create' => [
                static fn (RecordingApi $api) => (new Index($api))->create(
                    (new IndexEntity())->setName($invalid),
                    'db',
                    'ns'
                ),
            ],
            'Item::add' => [
                static fn (RecordingApi $api) => $item($api)->add(['v' => $invalid]),
            ],
            'Item::update' => [
                static fn (RecordingApi $api) => $item($api)->update(['v' => $invalid]),
            ],
            'Item::delete' => [
                static fn (RecordingApi $api) => $item($api)->delete(['v' => $invalid]),
            ],
            'Namespaces::create' => [
                static fn (RecordingApi $api) => $namespaces($api)->create($invalid),
            ],
            'Namespaces::addMetaDataKey' => [
                static fn (RecordingApi $api) => $namespaces($api)->addMetaDataKey('ns', 'key', $invalid),
            ],
            'Namespaces::schema' => [
                static fn (RecordingApi $api) => $namespaces($api)->schema('ns', ['field' => $invalid]),
            ],
            'Query::createSdlQueryByHttpPost' => [
                static fn (RecordingApi $api) => (new Query($api))->createSdlQueryByHttpPost(['v' => $invalid]),
            ],
        ];
    }

    public function testNothingIsSentWhenEncodingFails(): void
    {
        $service = new Item($this->api);
        $service->setDatabase('db');
        $service->setNamespace('ns');

        try {
            $service->add(['v' => self::INVALID_UTF8]);
            $this->fail('Expected JsonException');
        } catch (\JsonException) {
            $this->assertSame([], $this->api->calls, 'no HTTP request must be issued for a broken payload');
        }
    }
}
