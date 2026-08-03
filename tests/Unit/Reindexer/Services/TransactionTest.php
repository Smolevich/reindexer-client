<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reindexer\Services\Transaction;
use Tests\Unit\Reindexer\Support\RecordingApi;

#[CoversClass(Transaction::class)]
class TransactionTest extends TestCase
{
    private RecordingApi $api;
    private Transaction $service;

    protected function setUp(): void
    {
        $this->api = new RecordingApi();
        $this->service = new Transaction($this->api);
        $this->service->setDatabase('db');
    }

    public function testDatabaseAccessors(): void
    {
        $service = new Transaction($this->api);
        $this->assertSame('', $service->getDatabase());

        $service->setDatabase('mydb');
        $this->assertSame('mydb', $service->getDatabase());
    }

    public function testBeginPostsToNamespaceEndpoint(): void
    {
        $this->api->willRespond('{"tx_id":"12345"}');

        $response = $this->service->begin('ns');

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/namespaces/ns/transactions/begin', $call['uri']);
        $this->assertNull($call['body']);
        $this->assertSame('12345', $response->getDecodedResponseBody(true)['tx_id']);
    }

    public function testBeginEncodesDatabaseAndNamespace(): void
    {
        $this->service->setDatabase('my db');
        $this->service->begin('ns/evil?x=1');

        $this->assertSame(
            '/api/v1/db/my%20db/namespaces/ns%2Fevil%3Fx%3D1/transactions/begin',
            $this->api->lastCall()['uri']
        );
    }

    #[DataProvider('itemMethodProvider')]
    public function testItemOperationsUseTransactionItemsEndpoint(string $serviceMethod, string $httpMethod): void
    {
        $this->service->{$serviceMethod}('tx1', ['id' => 1, 'name' => 'первый']);

        $call = $this->api->lastCall();
        $this->assertSame($httpMethod, $call['method']);
        $this->assertSame('/api/v1/db/db/transactions/tx1/items', $call['uri']);
        $this->assertSame(['id' => 1, 'name' => 'первый'], json_decode($call['body'], true));
        $this->assertStringContainsString('"первый"', $call['body']);
    }

    public static function itemMethodProvider(): array
    {
        return [
            'addItem => POST' => ['addItem', 'POST'],
            'updateItem => PUT' => ['updateItem', 'PUT'],
            'upsertItem => PATCH' => ['upsertItem', 'PATCH'],
            'deleteItem => DELETE' => ['deleteItem', 'DELETE'],
        ];
    }

    #[DataProvider('itemMethodProvider')]
    public function testItemOperationsAppendExplodedPrecepts(string $serviceMethod, string $httpMethod): void
    {
        $this->service->{$serviceMethod}('tx1', ['name' => 'x'], ['id=serial()', 'updated_at=now()']);

        $call = $this->api->lastCall();
        $this->assertSame($httpMethod, $call['method']);
        $this->assertSame(
            '/api/v1/db/db/transactions/tx1/items?precepts=id%3Dserial%28%29&precepts=updated_at%3Dnow%28%29',
            $call['uri']
        );
    }

    public function testItemOperationWithSinglePrecept(): void
    {
        $this->service->addItem('tx1', ['name' => 'x'], ['id=serial()']);

        $this->assertSame(
            '/api/v1/db/db/transactions/tx1/items?precepts=id%3Dserial%28%29',
            $this->api->lastCall()['uri']
        );
    }

    public function testItemOperationWithoutPreceptsHasNoQueryString(): void
    {
        $this->service->addItem('tx1', ['id' => 1]);

        $this->assertSame('/api/v1/db/db/transactions/tx1/items', $this->api->lastCall()['uri']);
    }

    public function testItemOperationEncodesTransactionId(): void
    {
        $this->service->addItem('tx/1?x', ['id' => 1]);

        $this->assertSame('/api/v1/db/db/transactions/tx%2F1%3Fx/items', $this->api->lastCall()['uri']);
    }

    public function testEmptyItemPayloadSerializesToEmptyJsonArray(): void
    {
        $this->service->addItem('tx1');

        $this->assertSame('[]', $this->api->lastCall()['body']);
    }

    public function testSqlQueryUrlencodesQuery(): void
    {
        $this->service->sqlQuery('tx1', "UPDATE ns SET name = 'значение' WHERE id = 1");

        $call = $this->api->lastCall();
        $this->assertSame('GET', $call['method']);
        $this->assertSame(
            '/api/v1/db/db/transactions/tx1/query?q=' . urlencode("UPDATE ns SET name = 'значение' WHERE id = 1"),
            $call['uri']
        );
        $this->assertNull($call['body']);
    }

    public function testDeleteQuerySendsDslBody(): void
    {
        $this->service->deleteQuery('tx1', [
            'namespace' => 'ns',
            'filters' => [['field' => 'id', 'cond' => 'EQ', 'value' => 1]],
        ]);

        $call = $this->api->lastCall();
        $this->assertSame('DELETE', $call['method']);
        $this->assertSame('/api/v1/db/db/transactions/tx1/query', $call['uri']);
        $this->assertSame(
            '{"namespace":"ns","filters":[{"field":"id","cond":"EQ","value":1}]}',
            $call['body']
        );
    }

    public function testDeleteQueryPassesRawJsonStringThrough(): void
    {
        $raw = '{"namespace":"ns"}';
        $this->service->deleteQuery('tx1', $raw);

        $this->assertSame($raw, $this->api->lastCall()['body']);
    }

    public function testDeleteQueryAcceptsSdlQueryEntity(): void
    {
        $query = new class () extends \Reindexer\Entities\SdlQuery {
            public function __construct()
            {
                $this->namespace = 'items';
                $this->filters = [['field' => 'id', 'cond' => 'EQ', 'value' => 5]];
            }
        };

        $this->service->deleteQuery('tx1', $query);

        $this->assertSame(
            '{"namespace":"items","filters":[{"field":"id","cond":"EQ","value":5}]}',
            $this->api->lastCall()['body']
        );
    }

    public function testCommitPostsToCommitEndpoint(): void
    {
        $this->service->commit('tx9');

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/transactions/tx9/commit', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testRollbackPostsToRollbackEndpoint(): void
    {
        $this->service->rollback('tx9');

        $call = $this->api->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertSame('/api/v1/db/db/transactions/tx9/rollback', $call['uri']);
        $this->assertNull($call['body']);
    }

    public function testCommitEncodesTransactionId(): void
    {
        $this->service->commit('tx/9?x');

        $this->assertSame('/api/v1/db/db/transactions/tx%2F9%3Fx/commit', $this->api->lastCall()['uri']);
    }

    public function testVersionOverrideIsRespected(): void
    {
        $this->service->setVersion('v9');
        $this->service->begin('ns');

        $this->assertStringStartsWith('/api/v9/db/db/namespaces/ns/', $this->api->lastCall()['uri']);
    }
}
