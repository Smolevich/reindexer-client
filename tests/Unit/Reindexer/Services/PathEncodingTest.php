<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\BaseService;
use Reindexer\Services\Database;
use Reindexer\Services\Index;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;
use Tests\Unit\Reindexer\Support\RecordingApi;

/**
 * Regression tests: user-supplied path segments (database, namespace, index
 * and meta-key names) must be rawurlencode()d. A name such as
 * "users/metalist?with_values=true" used to be spliced verbatim into the URI
 * and silently changed the endpoint being called.
 */
#[CoversClass(Database::class)]
#[CoversClass(Namespaces::class)]
#[CoversClass(Index::class)]
#[CoversClass(Item::class)]
#[CoversClass(Query::class)]
#[CoversClass(BaseService::class)]
class PathEncodingTest extends TestCase
{
    private const EVIL_NS = 'users/metalist?with_values=true';
    private const EVIL_NS_ENCODED = 'users%2Fmetalist%3Fwith_values%3Dtrue';

    private RecordingApi $api;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
    }

    private function namespaces(string $database = 'db'): Namespaces
    {
        $service = new Namespaces($this->api);
        $service->setDatabase($database);

        return $service;
    }

    public function testDatabaseDropEncodesName(): void
    {
        (new Database($this->api))->drop('my db#1');

        $this->assertSame('/api/v1/db/my%20db%231', $this->api->lastCall()['uri']);
    }

    public function testNamespacesGetListEncodesDatabase(): void
    {
        $this->namespaces('my db')->getList('asc');

        $this->assertSame('/api/v1/db/my%20db/namespaces?sort_order=asc', $this->api->lastCall()['uri']);
    }

    public function testNamespacesCreateEncodesDatabase(): void
    {
        $this->namespaces('my db')->create('ns');

        $this->assertSame('/api/v1/db/my%20db/namespaces', $this->api->lastCall()['uri']);
    }

    public function testNamespacesDropEncodesName(): void
    {
        $this->namespaces()->drop(self::EVIL_NS);

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED,
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesGetEncodesName(): void
    {
        $this->namespaces()->get(self::EVIL_NS);

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED,
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesTruncateEncodesName(): void
    {
        $this->namespaces()->truncate(self::EVIL_NS);

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED . '/truncate',
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesRenameEncodesBothNames(): void
    {
        $this->namespaces()->rename('old/ns', 'new?ns');

        $this->assertSame(
            '/api/v1/db/db/namespaces/old%2Fns/rename/new%3Fns',
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesGetMetaListEncodesNameAndKeepsQueryParams(): void
    {
        $this->namespaces()->getMetaList(self::EVIL_NS, 10, 0, 'asc', true);

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED . '/metalist?limit=10&with_values=true&sort_order=asc',
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesAddMetaDataKeyEncodesName(): void
    {
        $this->namespaces()->addMetaDataKey(self::EVIL_NS, 'k', 'v');

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED . '/metabykey',
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesGetMetaDataKeyEncodesNameAndKey(): void
    {
        $this->namespaces()->getMetaDataKey(self::EVIL_NS, 'meta/key?x=1');

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED . '/metabykey/meta%2Fkey%3Fx%3D1',
            $this->api->lastCall()['uri']
        );
    }

    public function testNamespacesSchemaEncodesName(): void
    {
        $this->namespaces()->schema(self::EVIL_NS, ['type' => 'object']);

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . self::EVIL_NS_ENCODED . '/schema',
            $this->api->lastCall()['uri']
        );
    }

    public function testIndexServiceEncodesAllSegments(): void
    {
        $service = new Index($this->api);

        $service->get('my db', self::EVIL_NS);
        $this->assertSame(
            '/api/v1/db/my%20db/namespaces/' . self::EVIL_NS_ENCODED . '/indexes',
            $this->api->lastCall()['uri']
        );

        $service->delete('my db', self::EVIL_NS, 'idx/1');
        $this->assertSame(
            '/api/v1/db/my%20db/namespaces/' . self::EVIL_NS_ENCODED . '/indexes/idx%2F1',
            $this->api->lastCall()['uri']
        );
    }

    public function testIndexServiceCreateEncodesSegments(): void
    {
        $index = (new \Reindexer\Entities\Index())->setName('id');
        (new Index($this->api))->create($index, 'my db', self::EVIL_NS);

        $this->assertSame(
            '/api/v1/db/my%20db/namespaces/' . self::EVIL_NS_ENCODED . '/indexes',
            $this->api->lastCall()['uri']
        );
    }

    public function testItemServiceEncodesDatabaseAndNamespace(): void
    {
        $service = new Item($this->api);
        $service->setDatabase('my db');
        $service->setNamespace(self::EVIL_NS);
        $expected = '/api/v1/db/my%20db/namespaces/' . self::EVIL_NS_ENCODED . '/items';

        $service->add(['id' => 1]);
        $this->assertSame($expected, $this->api->lastCall()['uri']);

        $service->update(['id' => 1]);
        $this->assertSame($expected, $this->api->lastCall()['uri']);

        $service->delete(['id' => 1]);
        $this->assertSame($expected, $this->api->lastCall()['uri']);

        $service->get(5);
        $this->assertSame($expected . '?limit=5', $this->api->lastCall()['uri']);
    }

    public function testQueryServiceEncodesDatabaseSegment(): void
    {
        $service = new Query($this->api);
        $service->setDatabase('my db#1');

        $service->createByHttpGet('SELECT 1');
        $this->assertStringStartsWith('/api/v1/db/my%20db%231/query?q=', $this->api->lastCall()['uri']);

        $service->createSqlQueryByHttpPost('SELECT 1');
        $this->assertSame('/api/v1/db/my%20db%231/sqlquery', $this->api->lastCall()['uri']);

        $service->createSdlQueryByHttpPost('{"namespace":"ns"}');
        $this->assertSame('/api/v1/db/my%20db%231/query', $this->api->lastCall()['uri']);
    }

    public function testPlainNamesAreLeftIntact(): void
    {
        $this->namespaces()->get('items_2024');

        $this->assertSame('/api/v1/db/db/namespaces/items_2024', $this->api->lastCall()['uri']);
    }

    public function testUnicodeNamespaceNameIsPercentEncoded(): void
    {
        $this->namespaces()->get('пространство');

        $this->assertSame(
            '/api/v1/db/db/namespaces/' . rawurlencode('пространство'),
            $this->api->lastCall()['uri']
        );
    }
}
