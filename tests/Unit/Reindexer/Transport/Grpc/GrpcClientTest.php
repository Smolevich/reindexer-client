<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Transport\Grpc;

use Grpc\BidiStreamingCall;
use Grpc\ServerStreamingCall;
use Grpc\UnaryCall;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Grpc\AddIndexRequest;
use Reindexer\Grpc\AddTxItemRequest;
use Reindexer\Grpc\BeginTransactionRequest;
use Reindexer\Grpc\CommitTransactionRequest;
use Reindexer\Grpc\ConnectRequest;
use Reindexer\Grpc\CreateDatabaseRequest;
use Reindexer\Grpc\DropIndexRequest;
use Reindexer\Grpc\DropNamespaceRequest;
use Reindexer\Grpc\EncodingType;
use Reindexer\Grpc\EnumDatabasesResponse;
use Reindexer\Grpc\EnumNamespacesResponse;
use Reindexer\Grpc\ErrorResponse;
use Reindexer\Grpc\ErrorResponse\ErrorCode;
use Reindexer\Grpc\ModifyItemRequest;
use Reindexer\Grpc\ModifyMode;
use Reindexer\Grpc\OpenNamespaceRequest;
use Reindexer\Grpc\PBNamespace;
use Reindexer\Grpc\QueryResultsResponse;
use Reindexer\Grpc\ReindexerClient;
use Reindexer\Grpc\RollbackTransactionRequest;
use Reindexer\Grpc\SelectRequest;
use Reindexer\Grpc\SqlRequest;
use Reindexer\Grpc\TransactionIdResponse;
use Reindexer\Grpc\UpdateIndexRequest;
use Reindexer\Transport\Grpc\GrpcClient;

/**
 * Deep unit tests for GrpcClient logic (request building, response decoding,
 * error mapping). The generated stub is replaced with a mock via reflection,
 * so no grpc extension and no server are required.
 */
#[CoversClass(GrpcClient::class)]
#[CoversClass(GrpcException::class)]
class GrpcClientTest extends TestCase
{
    private ReindexerClient&MockObject $stub;
    private GrpcClient $client;

    protected function setUp(): void
    {
        $this->stub = $this->getMockBuilder(ReindexerClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass(GrpcClient::class);
        $this->client = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('stub')->setValue($this->client, $this->stub);
        $reflection->getProperty('target')->setValue($this->client, 'test:16534');
    }

    private function connectClient(string $dbName = 'testdb'): void
    {
        (new \ReflectionClass(GrpcClient::class))
            ->getProperty('dbName')
            ->setValue($this->client, $dbName);
    }

    private function okError(): ErrorResponse
    {
        return (new ErrorResponse())->setCode(ErrorCode::errCodeOK)->setWhat('');
    }

    private function failedError(int $code = ErrorCode::errCodeNotFound, string $what = 'Namespace not found'): ErrorResponse
    {
        return (new ErrorResponse())->setCode($code)->setWhat($what);
    }

    private function okStatus(): object
    {
        return (object) ['code' => 0, 'details' => ''];
    }

    private function unaryCall(object $response, ?object $status = null): UnaryCall&MockObject
    {
        $call = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['wait'])
            ->getMock();
        $call->method('wait')->willReturn([$response, $status ?? $this->okStatus()]);

        return $call;
    }

    private function nullResponseCall(?object $status = null): UnaryCall&MockObject
    {
        $call = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['wait'])
            ->getMock();
        $call->method('wait')->willReturn([null, $status ?? $this->okStatus()]);

        return $call;
    }

    /**
     * @param QueryResultsResponse[] $responses
     */
    private function streamingCall(array $responses, ?object $status = null): ServerStreamingCall&MockObject
    {
        $call = $this->getMockBuilder(ServerStreamingCall::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['responses', 'getStatus'])
            ->getMock();
        $call->method('responses')->willReturnCallback(static function () use ($responses) {
            yield from $responses;
        });
        $call->method('getStatus')->willReturn($status ?? $this->okStatus());

        return $call;
    }

    private function queryResult(string $data, ?ErrorResponse $error = null): QueryResultsResponse
    {
        $response = (new QueryResultsResponse())->setData($data);
        if ($error !== null) {
            $response->setErrorResponse($error);
        }

        return $response;
    }

    public function testGetTarget(): void
    {
        $this->assertSame('test:16534', $this->client->getTarget());
    }

    public function testGetStub(): void
    {
        $this->assertSame($this->stub, $this->client->getStub());
    }

    // ---- connect ----------------------------------------------------------

    public function testConnectSendsDefaultCprotoUrlAndDbName(): void
    {
        $captured = null;
        $this->stub->expects($this->once())
            ->method('Connect')
            ->willReturnCallback(function (ConnectRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->connect('mydb');

        $this->assertSame('cproto://127.0.0.1:6534/mydb', $captured->getUrl());
        $this->assertSame('mydb', $captured->getDbName());
        $this->assertSame('', $captured->getLogin());
        $this->assertSame('', $captured->getPassword());
        $this->assertNotNull($captured->getConnectOpts());
    }

    public function testConnectWithExplicitUrlAndCredentials(): void
    {
        $captured = null;
        $this->stub->method('Connect')
            ->willReturnCallback(function (ConnectRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->connect('mydb', 'cproto://10.0.0.1:6534/mydb', 'user', 'secret');

        $this->assertSame('cproto://10.0.0.1:6534/mydb', $captured->getUrl());
        $this->assertSame('user', $captured->getLogin());
        $this->assertSame('secret', $captured->getPassword());
    }

    public function testConnectErrorThrowsAndDoesNotBindDatabase(): void
    {
        $this->stub->method('Connect')->willReturn(
            $this->unaryCall($this->failedError(ErrorCode::errCodeParams, 'db not found'))
        );

        try {
            $this->client->connect('mydb');
            $this->fail('Expected GrpcException');
        } catch (GrpcException $e) {
            $this->assertSame(ErrorCode::errCodeParams, $e->getCode());
            $this->assertSame('Reindexer gRPC error 3: db not found', $e->getMessage());
        }

        // db must not be bound: any RPC still fails with the guard error
        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Not connected');
        $this->client->dropNamespace('ns');
    }

    // ---- database RPCs ----------------------------------------------------

    public function testCreateDatabase(): void
    {
        $captured = null;
        $this->stub->expects($this->once())
            ->method('CreateDatabase')
            ->willReturnCallback(function (CreateDatabaseRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->createDatabase('newdb');

        $this->assertSame('newdb', $captured->getDbName());
    }

    public function testEnumDatabasesReturnsNames(): void
    {
        $response = new EnumDatabasesResponse();
        $response->setNames(['db1', 'db2'])->setErrorResponse($this->okError());
        $this->stub->method('EnumDatabases')->willReturn($this->unaryCall($response));

        $this->assertSame(['db1', 'db2'], $this->client->enumDatabases());
    }

    public function testEnumDatabasesPropagatesServerError(): void
    {
        $response = new EnumDatabasesResponse();
        $response->setNames([])->setErrorResponse($this->failedError(ErrorCode::errCodeForbidden, 'no access'));
        $this->stub->method('EnumDatabases')->willReturn($this->unaryCall($response));

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Reindexer gRPC error 9: no access');
        $this->client->enumDatabases();
    }

    // ---- namespaces -------------------------------------------------------

    public function testOpenNamespaceUsesStorageDefaults(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('OpenNamespace')
            ->willReturnCallback(function (OpenNamespaceRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->openNamespace('items');

        $this->assertSame('testdb', $captured->getDbName());
        $storage = $captured->getStorageOptions();
        $this->assertSame('items', $storage->getNsName());
        $this->assertTrue($storage->getEnabled());
        $this->assertTrue($storage->getCreateIfMissing());
        $this->assertFalse($storage->getDropOnFileFormatError());
    }

    public function testOpenNamespaceRespectsStorageOverrides(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('OpenNamespace')
            ->willReturnCallback(function (OpenNamespaceRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->openNamespace('items', [
            'enabled' => false,
            'createIfMissing' => false,
            'dropOnFileFormatError' => true,
        ]);

        $storage = $captured->getStorageOptions();
        $this->assertFalse($storage->getEnabled());
        $this->assertFalse($storage->getCreateIfMissing());
        $this->assertTrue($storage->getDropOnFileFormatError());
    }

    public function testDropNamespacePassesDbAndNsNames(): void
    {
        $this->connectClient('db_x');
        $captured = null;
        $this->stub->method('DropNamespace')
            ->willReturnCallback(function (DropNamespaceRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->dropNamespace('gone');

        $this->assertSame('db_x', $captured->getDbName());
        $this->assertSame('gone', $captured->getNsName());
    }

    public function testTruncateNamespaceErrorMapping(): void
    {
        $this->connectClient();
        $this->stub->method('TruncateNamespace')->willReturn(
            $this->unaryCall($this->failedError())
        );

        $this->expectException(GrpcException::class);
        $this->expectExceptionCode(ErrorCode::errCodeNotFound);
        $this->client->truncateNamespace('missing');
    }

    public function testCloseNamespace(): void
    {
        $this->connectClient();
        $this->stub->expects($this->once())->method('CloseNamespace')->willReturn(
            $this->unaryCall($this->okError())
        );

        $this->client->closeNamespace('items');
    }

    public function testEnumNamespacesBuildsOptionsAndExtractsNames(): void
    {
        $this->connectClient();
        $captured = null;
        $response = new EnumNamespacesResponse();
        $response->setNamespacesDefinitions([
            (new PBNamespace())->setName('ns_a'),
            (new PBNamespace())->setName('ns_b'),
        ]);
        $this->stub->method('EnumNamespaces')
            ->willReturnCallback(function ($request) use (&$captured, $response) {
                $captured = $request;

                return $this->unaryCall($response);
            });

        $names = $this->client->enumNamespaces('prefix', true);

        $this->assertSame(['ns_a', 'ns_b'], $names);
        $options = $captured->getOptions();
        $this->assertSame('prefix', $options->getFilter());
        $this->assertTrue($options->getWithClosed());
        $this->assertTrue($options->getOnlyNames());
        $this->assertTrue($options->getHideSystems());
    }

    // ---- indexes ----------------------------------------------------------

    public function testAddIndexAppliesDefaults(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('AddIndex')
            ->willReturnCallback(function (AddIndexRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->addIndex('items', ['name' => 'id']);

        $definition = $captured->getDefinition();
        $this->assertSame('id', $definition->getName());
        $this->assertSame(['id'], iterator_to_array($definition->getJsonPaths(), false));
        $this->assertSame('hash', $definition->getIndexType());
        $this->assertSame('string', $definition->getFieldType());
        $this->assertSame(0, $definition->getExpireAfter());
        $options = $definition->getOptions();
        $this->assertFalse($options->getIsPk());
        $this->assertFalse($options->getIsArray());
        $this->assertFalse($options->getIsDense());
        $this->assertFalse($options->getIsSparse());
    }

    public function testAddIndexFullDefinition(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('AddIndex')
            ->willReturnCallback(function (AddIndexRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->addIndex('items', [
            'name' => 'ttl_field',
            'jsonPaths' => ['a', 'b'],
            'indexType' => 'ttl',
            'fieldType' => 'int64',
            'isPk' => true,
            'isArray' => true,
            'isDense' => true,
            'isSparse' => true,
            'expireAfter' => 3600,
        ]);

        $definition = $captured->getDefinition();
        $this->assertSame(['a', 'b'], iterator_to_array($definition->getJsonPaths(), false));
        $this->assertSame('ttl', $definition->getIndexType());
        $this->assertSame('int64', $definition->getFieldType());
        $this->assertSame(3600, $definition->getExpireAfter());
        $options = $definition->getOptions();
        $this->assertTrue($options->getIsPk());
        $this->assertTrue($options->getIsArray());
        $this->assertTrue($options->getIsDense());
        $this->assertTrue($options->getIsSparse());
    }

    #[DataProvider('indexMethodProvider')]
    public function testIndexDefinitionRequiresName(string $method): void
    {
        $this->connectClient();
        $this->stub->expects($this->never())->method($this->anything());

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Index definition requires a non-empty "name"');
        $this->client->{$method}('items', ['fieldType' => 'int']);
    }

    #[DataProvider('indexMethodProvider')]
    public function testIndexDefinitionRejectsEmptyName(string $method): void
    {
        $this->connectClient();

        $this->expectException(GrpcException::class);
        $this->client->{$method}('items', ['name' => '']);
    }

    public static function indexMethodProvider(): array
    {
        return [
            'addIndex' => ['addIndex'],
            'updateIndex' => ['updateIndex'],
            'dropIndex' => ['dropIndex'],
        ];
    }

    public function testUpdateIndexAndDropIndexSendRequests(): void
    {
        $this->connectClient();
        $capturedUpdate = null;
        $capturedDrop = null;
        $this->stub->method('UpdateIndex')
            ->willReturnCallback(function (UpdateIndexRequest $request) use (&$capturedUpdate) {
                $capturedUpdate = $request;

                return $this->unaryCall($this->okError());
            });
        $this->stub->method('DropIndex')
            ->willReturnCallback(function (DropIndexRequest $request) use (&$capturedDrop) {
                $capturedDrop = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->updateIndex('items', ['name' => 'idx', 'indexType' => 'tree']);
        $this->client->dropIndex('items', ['name' => 'idx']);

        $this->assertSame('tree', $capturedUpdate->getDefinition()->getIndexType());
        $this->assertSame('idx', $capturedDrop->getDefinition()->getName());
    }

    // ---- queries ----------------------------------------------------------

    public function testExecSqlStreamsDecodedItems(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('ExecSql')
            ->willReturnCallback(function (SqlRequest $request) use (&$captured) {
                $captured = $request;

                return $this->streamingCall([
                    $this->queryResult('[{"id":1},{"id":2}]'),
                    $this->queryResult('[{"id":3}]'),
                ]);
            });

        $items = iterator_to_array($this->client->execSql('SELECT * FROM items'), false);

        $this->assertSame([['id' => 1], ['id' => 2], ['id' => 3]], $items);
        $this->assertSame('SELECT * FROM items', $captured->getSql());
        $this->assertSame('testdb', $captured->getDbName());
        $this->assertSame(EncodingType::JSON, $captured->getFlags()->getEncodingType());
    }

    public function testStreamResultsSupportsItemsEnvelope(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall([
            $this->queryResult('{"items":[{"id":10}],"total_items":1}'),
        ]));

        $items = iterator_to_array($this->client->execSql('SELECT 1'), false);

        $this->assertSame([['id' => 10]], $items);
    }

    public function testStreamResultsWrapsSingleObjectPayload(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall([
            $this->queryResult('{"id":42,"name":"solo"}'),
        ]));

        $items = iterator_to_array($this->client->execSql('SELECT 1'), false);

        $this->assertSame([['id' => 42, 'name' => 'solo']], $items);
    }

    public function testStreamResultsSkipsEmptyChunks(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall([
            $this->queryResult(''),
            $this->queryResult('[{"id":1}]'),
            $this->queryResult(''),
        ]));

        $items = iterator_to_array($this->client->execSql('SELECT 1'), false);

        $this->assertSame([['id' => 1]], $items);
    }

    public function testStreamResultsThrowsOnInvalidJson(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall([
            $this->queryResult('{"broken":'),
        ]));

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Failed to decode JSON query results');
        iterator_to_array($this->client->execSql('SELECT 1'), false);
    }

    public function testStreamResultsThrowsOnScalarPayload(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall([
            $this->queryResult('42'),
        ]));

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Unexpected JSON query results payload: int');
        iterator_to_array($this->client->execSql('SELECT 1'), false);
    }

    public function testStreamResultsPropagatesErrorResponse(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall([
            $this->queryResult('', $this->failedError(ErrorCode::errCodeParseSQL, 'syntax error')),
        ]));

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Reindexer gRPC error 1: syntax error');
        iterator_to_array($this->client->execSql('SELEKT'), false);
    }

    public function testStreamResultsChecksFinalStatus(): void
    {
        $this->connectClient();
        $this->stub->method('ExecSql')->willReturn($this->streamingCall(
            [$this->queryResult('[{"id":1}]')],
            (object) ['code' => 14, 'details' => 'unavailable']
        ));

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('gRPC call failed with status 14: unavailable');
        iterator_to_array($this->client->execSql('SELECT 1'), false);
    }

    public function testSelectEncodesArrayDslWithUnescapedUnicode(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('Select')
            ->willReturnCallback(function (SelectRequest $request) use (&$captured) {
                $captured = $request;

                return $this->streamingCall([]);
            });

        iterator_to_array($this->client->select([
            'namespace' => 'items',
            'filters' => [['field' => 'name', 'cond' => 'EQ', 'value' => 'Пример']],
        ]), false);

        $query = $captured->getQuery();
        $this->assertSame(EncodingType::JSON, $query->getEncodingType());
        $this->assertStringContainsString('"Пример"', $query->getData());
        $decoded = json_decode($query->getData(), true);
        $this->assertSame('items', $decoded['namespace']);
    }

    public function testSelectPassesRawJsonStringThrough(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('Select')
            ->willReturnCallback(function (SelectRequest $request) use (&$captured) {
                $captured = $request;

                return $this->streamingCall([]);
            });

        $raw = '{"namespace":"items","limit":1}';
        iterator_to_array($this->client->select($raw), false);

        $this->assertSame($raw, $captured->getQuery()->getData());
    }

    public function testUpdateAndDeleteUseQueryDsl(): void
    {
        $this->connectClient();
        $capturedUpdate = null;
        $capturedDelete = null;
        $this->stub->method('Update')
            ->willReturnCallback(function ($request) use (&$capturedUpdate) {
                $capturedUpdate = $request;

                return $this->streamingCall([$this->queryResult('[{"id":1,"updated":true}]')]);
            });
        $this->stub->method('Delete')
            ->willReturnCallback(function ($request) use (&$capturedDelete) {
                $capturedDelete = $request;

                return $this->streamingCall([]);
            });

        $updated = iterator_to_array($this->client->update('{"namespace":"items"}'), false);
        $deleted = iterator_to_array($this->client->delete('{"namespace":"items"}'), false);

        $this->assertSame([['id' => 1, 'updated' => true]], $updated);
        $this->assertSame([], $deleted);
        $this->assertSame('{"namespace":"items"}', $capturedUpdate->getQuery()->getData());
        $this->assertSame('{"namespace":"items"}', $capturedDelete->getQuery()->getData());
    }

    // ---- modify items / transactions --------------------------------------

    /**
     * @param ErrorResponse[] $readResponses
     */
    private function bidiCall(array &$written, array $readResponses = [], ?object $status = null): BidiStreamingCall&MockObject
    {
        $call = $this->getMockBuilder(BidiStreamingCall::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['write', 'writesDone', 'read', 'getStatus'])
            ->getMock();
        $call->method('write')->willReturnCallback(function ($request) use (&$written) {
            $written[] = $request;
        });
        $call->method('read')->willReturnCallback(function () use (&$readResponses) {
            return array_shift($readResponses);
        });
        $call->method('getStatus')->willReturn($status ?? $this->okStatus());

        return $call;
    }

    public function testModifyItemsWritesOneRequestPerItem(): void
    {
        $this->connectClient();
        $written = [];
        $this->stub->method('ModifyItem')->willReturn($this->bidiCall($written));

        $this->client->modifyItems('items', [
            ['id' => 1, 'name' => 'раз'],
            '{"id":2,"name":"two"}',
        ], ModifyMode::INSERT);

        $this->assertCount(2, $written);
        /** @var ModifyItemRequest $first */
        [$first, $second] = $written;
        $this->assertSame('testdb', $first->getDbName());
        $this->assertSame('items', $first->getNsName());
        $this->assertSame(ModifyMode::INSERT, $first->getMode());
        $this->assertSame(EncodingType::JSON, $first->getEncodingType());
        $this->assertSame('{"id":1,"name":"раз"}', $first->getData());
        $this->assertSame('{"id":2,"name":"two"}', $second->getData());
    }

    public function testModifyItemsDefaultsToUpsert(): void
    {
        $this->connectClient();
        $written = [];
        $this->stub->method('ModifyItem')->willReturn($this->bidiCall($written));

        $this->client->modifyItems('items', [['id' => 1]]);

        $this->assertSame(ModifyMode::UPSERT, $written[0]->getMode());
    }

    public function testModifyItemsThrowsOnServerError(): void
    {
        $this->connectClient();
        $written = [];
        $this->stub->method('ModifyItem')->willReturn(
            $this->bidiCall($written, [$this->failedError(ErrorCode::errCodeLogic, 'PK is missing')])
        );

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Reindexer gRPC error 4: PK is missing');
        $this->client->modifyItems('items', [['name' => 'no-pk']]);
    }

    public function testModifyItemsChecksTransportStatus(): void
    {
        $this->connectClient();
        $written = [];
        $this->stub->method('ModifyItem')->willReturn(
            $this->bidiCall($written, [], (object) ['code' => 13, 'details' => 'internal'])
        );

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('gRPC call failed with status 13: internal');
        $this->client->modifyItems('items', [['id' => 1]]);
    }

    public function testBeginTransactionReturnsId(): void
    {
        $this->connectClient();
        $captured = null;
        $response = (new TransactionIdResponse())
            ->setId(77)
            ->setStatus($this->okError());
        $this->stub->method('BeginTransaction')
            ->willReturnCallback(function (BeginTransactionRequest $request) use (&$captured, $response) {
                $captured = $request;

                return $this->unaryCall($response);
            });

        $id = $this->client->beginTransaction('items');

        $this->assertSame(77, $id);
        $this->assertSame('testdb', $captured->getDbName());
        $this->assertSame('items', $captured->getNsName());
    }

    public function testBeginTransactionErrorMapping(): void
    {
        $this->connectClient();
        $response = (new TransactionIdResponse())
            ->setStatus($this->failedError(ErrorCode::errCodeBadTransaction, 'tx failed'));
        $this->stub->method('BeginTransaction')->willReturn($this->unaryCall($response));

        $this->expectException(GrpcException::class);
        $this->expectExceptionCode(ErrorCode::errCodeBadTransaction);
        $this->client->beginTransaction('items');
    }

    public function testAddTxItemsWritesRequestsWithTransactionId(): void
    {
        $this->connectClient();
        $written = [];
        $this->stub->method('AddTxItem')->willReturn($this->bidiCall($written));

        $this->client->addTxItems(42, [['id' => 1]], ModifyMode::DELETE);

        $this->assertCount(1, $written);
        /** @var AddTxItemRequest $request */
        $request = $written[0];
        $this->assertSame(42, $request->getId());
        $this->assertSame(ModifyMode::DELETE, $request->getMode());
        $this->assertSame(EncodingType::JSON, $request->getEncodingType());
        $this->assertSame('{"id":1}', $request->getData());
    }

    public function testCommitTransactionSendsId(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('CommitTransaction')
            ->willReturnCallback(function (CommitTransactionRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->commitTransaction(42);

        $this->assertSame(42, $captured->getId());
    }

    public function testRollbackTransactionSendsId(): void
    {
        $this->connectClient();
        $captured = null;
        $this->stub->method('RollbackTransaction')
            ->willReturnCallback(function (RollbackTransactionRequest $request) use (&$captured) {
                $captured = $request;

                return $this->unaryCall($this->okError());
            });

        $this->client->rollbackTransaction(42);

        $this->assertSame(42, $captured->getId());
    }

    public function testCommitTransactionErrorMapping(): void
    {
        $this->connectClient();
        $this->stub->method('CommitTransaction')->willReturn(
            $this->unaryCall($this->failedError(ErrorCode::errCodeTxDoesNotExist, 'tx not found'))
        );

        $this->expectException(GrpcException::class);
        $this->expectExceptionCode(ErrorCode::errCodeTxDoesNotExist);
        $this->client->commitTransaction(999);
    }

    // ---- low level guards -------------------------------------------------

    public function testUnaryNullResponseThrows(): void
    {
        $this->stub->method('CreateDatabase')->willReturn($this->nullResponseCall());

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Empty response from Reindexer gRPC server');
        $this->client->createDatabase('db');
    }

    public function testUnaryTransportFailureThrowsBeforeReadingResponse(): void
    {
        $this->stub->method('CreateDatabase')->willReturn(
            $this->nullResponseCall((object) ['code' => 14, 'details' => 'connect failed'])
        );

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('gRPC call failed with status 14: connect failed');
        $this->client->createDatabase('db');
    }

    public function testStatusWithNullDetailsProducesEmptyString(): void
    {
        $this->stub->method('CreateDatabase')->willReturn(
            $this->nullResponseCall((object) ['code' => 2, 'details' => null])
        );

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('gRPC call failed with status 2: ');
        $this->client->createDatabase('db');
    }

    #[DataProvider('guardedMethodProvider')]
    public function testRpcMethodsRequireConnect(callable $invoker): void
    {
        $this->stub->expects($this->never())->method($this->anything());

        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Not connected: call connect($dbName) before other RPCs');
        $invoker($this->client);
    }

    public static function guardedMethodProvider(): array
    {
        return [
            'openNamespace' => [static fn (GrpcClient $c) => $c->openNamespace('ns')],
            'closeNamespace' => [static fn (GrpcClient $c) => $c->closeNamespace('ns')],
            'dropNamespace' => [static fn (GrpcClient $c) => $c->dropNamespace('ns')],
            'truncateNamespace' => [static fn (GrpcClient $c) => $c->truncateNamespace('ns')],
            'enumNamespaces' => [static fn (GrpcClient $c) => $c->enumNamespaces()],
            'addIndex' => [static fn (GrpcClient $c) => $c->addIndex('ns', ['name' => 'id'])],
            'updateIndex' => [static fn (GrpcClient $c) => $c->updateIndex('ns', ['name' => 'id'])],
            'dropIndex' => [static fn (GrpcClient $c) => $c->dropIndex('ns', ['name' => 'id'])],
            'execSql' => [static fn (GrpcClient $c) => $c->execSql('SELECT 1')],
            'select' => [static fn (GrpcClient $c) => $c->select('{}')],
            'update' => [static fn (GrpcClient $c) => $c->update('{}')],
            'delete' => [static fn (GrpcClient $c) => $c->delete('{}')],
            'modifyItems' => [static fn (GrpcClient $c) => $c->modifyItems('ns', [['id' => 1]])],
            'beginTransaction' => [static fn (GrpcClient $c) => $c->beginTransaction('ns')],
        ];
    }
}
