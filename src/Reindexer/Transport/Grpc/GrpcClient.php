<?php

declare(strict_types=1);

namespace Reindexer\Transport\Grpc;

use Generator;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Grpc\AddIndexRequest;
use Reindexer\Grpc\AddNamespaceRequest;
use Reindexer\Grpc\AddTxItemRequest;
use Reindexer\Grpc\BeginTransactionRequest;
use Reindexer\Grpc\CloseNamespaceRequest;
use Reindexer\Grpc\CommitTransactionRequest;
use Reindexer\Grpc\ConnectOptions;
use Reindexer\Grpc\ConnectRequest;
use Reindexer\Grpc\CreateDatabaseRequest;
use Reindexer\Grpc\DeleteMetaRequest;
use Reindexer\Grpc\DeleteRequest;
use Reindexer\Grpc\DropIndexRequest;
use Reindexer\Grpc\DropNamespaceRequest;
use Reindexer\Grpc\EncodingType;
use Reindexer\Grpc\EnumDatabasesRequest;
use Reindexer\Grpc\EnumMetaRequest;
use Reindexer\Grpc\EnumNamespacesOptions;
use Reindexer\Grpc\EnumNamespacesRequest;
use Reindexer\Grpc\ErrorResponse;
use Reindexer\Grpc\GetMetaRequest;
use Reindexer\Grpc\GetProtobufSchemaRequest;
use Reindexer\Grpc\Index;
use Reindexer\Grpc\IndexOptions;
use Reindexer\Grpc\Metadata;
use Reindexer\Grpc\ModifyItemRequest;
use Reindexer\Grpc\ModifyMode;
use Reindexer\Grpc\OpenNamespaceRequest;
use Reindexer\Grpc\OutputFlags;
use Reindexer\Grpc\PBNamespace;
use Reindexer\Grpc\PutMetaRequest;
use Reindexer\Grpc\Query;
use Reindexer\Grpc\ReindexerClient;
use Reindexer\Grpc\RollbackTransactionRequest;
use Reindexer\Grpc\SchemaDefinition;
use Reindexer\Grpc\SelectRequest;
use Reindexer\Grpc\SetSchemaRequest;
use Reindexer\Grpc\SqlRequest;
use Reindexer\Grpc\StorageOptions;
use Reindexer\Grpc\TruncateNamespaceRequest;
use Reindexer\Grpc\UpdateIndexRequest;
use Reindexer\Grpc\UpdateRequest;

/**
 * High level wrapper around the generated gRPC stub (\Reindexer\Grpc\ReindexerClient).
 *
 * The gRPC transport is optional: it requires the "grpc" PHP extension and the
 * composer packages grpc/grpc and google/protobuf (see "suggest" in composer.json).
 * The HTTP transport keeps working without any of them.
 */
class GrpcClient
{
    public const DEFAULT_TARGET = 'localhost:16534';

    /**
     * Default cproto endpoint used by ConnectRequest.url. The url is resolved
     * server side, therefore 127.0.0.1 points to the reindexer server itself.
     */
    public const DEFAULT_CPROTO_URL = 'cproto://127.0.0.1:6534';

    private const GRPC_STATUS_OK = 0;

    private ReindexerClient $stub;
    private string $dbName = '';

    public function __construct(
        private readonly string $target = self::DEFAULT_TARGET,
        array $stubOptions = [],
    ) {
        self::assertGrpcAvailable();

        if (!array_key_exists('credentials', $stubOptions)) {
            $stubOptions['credentials'] = \Grpc\ChannelCredentials::createInsecure();
        }

        $this->stub = new ReindexerClient($this->target, $stubOptions);
    }

    public static function assertGrpcAvailable(): void
    {
        self::assertGrpcDependencies(
            extension_loaded('grpc'),
            class_exists(\Grpc\BaseStub::class),
            class_exists(\Google\Protobuf\Internal\Message::class),
        );
    }

    /**
     * @internal split from assertGrpcAvailable() so every missing-dependency
     *           branch stays unit-testable in environments where the real
     *           packages are installed
     */
    public static function assertGrpcDependencies(
        bool $extensionLoaded,
        bool $grpcPackageInstalled,
        bool $protobufPackageInstalled,
    ): void {
        if (!$extensionLoaded) {
            throw new \RuntimeException(
                'reindexer-client: gRPC transport requires the "grpc" PHP extension (pecl install grpc).'
            );
        }

        if (!$grpcPackageInstalled || !$protobufPackageInstalled) {
            throw new \RuntimeException(
                'reindexer-client: gRPC transport requires composer packages grpc/grpc and google/protobuf.'
            );
        }
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getStub(): ReindexerClient
    {
        return $this->stub;
    }

    /**
     * Binds the gRPC channel to a database. Must be called before any other RPC.
     *
     * @param string|null $url cproto url of the server itself, e.g. "cproto://127.0.0.1:6534/dbname"
     */
    public function connect(
        string $dbName,
        ?string $url = null,
        string $login = '',
        string $password = '',
        ?ConnectOptions $options = null,
    ): void {
        $request = new ConnectRequest();
        $request->setUrl($url ?? self::DEFAULT_CPROTO_URL . '/' . $dbName)
            ->setDbName($dbName)
            ->setLogin($login)
            ->setPassword($password)
            ->setConnectOpts($options ?? new ConnectOptions());

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->Connect($request));
        $this->throwIfError($response);

        $this->dbName = $dbName;
    }

    public function createDatabase(string $dbName): void
    {
        $request = new CreateDatabaseRequest();
        $request->setDbName($dbName);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->CreateDatabase($request));
        $this->throwIfError($response);
    }

    /**
     * @return string[] database names
     */
    public function enumDatabases(): array
    {
        $response = $this->unary($this->stub->EnumDatabases(new EnumDatabasesRequest()));
        $this->throwIfError($response->getErrorResponse());

        return iterator_to_array($response->getNames(), false);
    }

    /**
     * @param array{enabled?: bool, createIfMissing?: bool, dropOnFileFormatError?: bool} $storage
     */
    public function openNamespace(string $nsName, array $storage = []): void
    {
        $storageOptions = new StorageOptions();
        $storageOptions->setNsName($nsName)
            ->setEnabled($storage['enabled'] ?? true)
            ->setCreateIfMissing($storage['createIfMissing'] ?? true)
            ->setDropOnFileFormatError($storage['dropOnFileFormatError'] ?? false);

        $request = new OpenNamespaceRequest();
        $request->setDbName($this->requireDbName())
            ->setStorageOptions($storageOptions);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->OpenNamespace($request));
        $this->throwIfError($response);
    }

    /**
     * Creates a namespace from a full definition: storage options plus index
     * definitions in one call (unlike openNamespace + addIndex per index).
     *
     * @param array{enabled?: bool, createIfMissing?: bool, dropOnFileFormatError?: bool} $storage
     * @param array<int, array<string, mixed>> $indexes index definitions, see addIndex()
     */
    public function addNamespace(string $nsName, array $storage = [], array $indexes = []): void
    {
        $dbName = $this->requireDbName();

        $storageOptions = new StorageOptions();
        $storageOptions->setNsName($nsName)
            ->setEnabled($storage['enabled'] ?? true)
            ->setCreateIfMissing($storage['createIfMissing'] ?? true)
            ->setDropOnFileFormatError($storage['dropOnFileFormatError'] ?? false);

        $namespace = new PBNamespace();
        $namespace->setDbName($dbName)
            ->setName($nsName)
            ->setStorageOptions($storageOptions)
            ->setIndexesDefinitions(array_map(
                fn (array $definition): Index => $this->buildIndex($definition),
                $indexes
            ));

        $request = new AddNamespaceRequest();
        $request->setDbName($dbName)->setNamespace($namespace);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->AddNamespace($request));
        $this->throwIfError($response);
    }

    public function closeNamespace(string $nsName): void
    {
        $request = new CloseNamespaceRequest();
        $request->setDbName($this->requireDbName())->setNsName($nsName);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->CloseNamespace($request));
        $this->throwIfError($response);
    }

    public function dropNamespace(string $nsName): void
    {
        $request = new DropNamespaceRequest();
        $request->setDbName($this->requireDbName())->setNsName($nsName);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->DropNamespace($request));
        $this->throwIfError($response);
    }

    public function truncateNamespace(string $nsName): void
    {
        $request = new TruncateNamespaceRequest();
        $request->setDbName($this->requireDbName())->setNsName($nsName);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->TruncateNamespace($request));
        $this->throwIfError($response);
    }

    /**
     * @return string[] namespace names
     */
    public function enumNamespaces(string $filter = '', bool $withClosed = false): array
    {
        $options = new EnumNamespacesOptions();
        $options->setFilter($filter)
            ->setWithClosed($withClosed)
            ->setOnlyNames(true)
            ->setHideSystems(true);

        $request = new EnumNamespacesRequest();
        $request->setDbName($this->requireDbName())->setOptions($options);

        $response = $this->unary($this->stub->EnumNamespaces($request));
        $this->throwIfError($response->getErrorResponse());

        $names = [];
        foreach ($response->getNamespacesDefinitions() as $namespace) {
            $names[] = $namespace->getName();
        }

        return $names;
    }

    /**
     * @param array{
     *     name: string,
     *     jsonPaths?: string[],
     *     indexType?: string,
     *     fieldType?: string,
     *     isPk?: bool,
     *     isArray?: bool,
     *     isDense?: bool,
     *     isSparse?: bool,
     *     expireAfter?: int
     * } $definition
     */
    public function addIndex(string $nsName, array $definition): void
    {
        $request = new AddIndexRequest();
        $request->setDbName($this->requireDbName())
            ->setNsName($nsName)
            ->setDefinition($this->buildIndex($definition));

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->AddIndex($request));
        $this->throwIfError($response);
    }

    /**
     * @param array<string, mixed> $definition see addIndex()
     */
    public function updateIndex(string $nsName, array $definition): void
    {
        $request = new UpdateIndexRequest();
        $request->setDbName($this->requireDbName())
            ->setNsName($nsName)
            ->setDefinition($this->buildIndex($definition));

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->UpdateIndex($request));
        $this->throwIfError($response);
    }

    /**
     * @param array<string, mixed> $definition see addIndex()
     */
    public function dropIndex(string $nsName, array $definition): void
    {
        $request = new DropIndexRequest();
        $request->setDbName($this->requireDbName())
            ->setNsName($nsName)
            ->setDefinition($this->buildIndex($definition));

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->DropIndex($request));
        $this->throwIfError($response);
    }

    /**
     * Executes an SQL query, streams decoded JSON items back.
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function execSql(string $sql): iterable
    {
        $request = new SqlRequest();
        $request->setDbName($this->requireDbName())
            ->setSql($sql)
            ->setFlags($this->jsonOutputFlags());

        return $this->streamResults($this->stub->ExecSql($request));
    }

    /**
     * Executes a Query-DSL (JSON) select query.
     *
     * @param array<string, mixed>|string $queryDsl
     * @return iterable<int, array<string, mixed>>
     */
    public function select(array|string $queryDsl): iterable
    {
        $request = new SelectRequest();
        $request->setDbName($this->requireDbName())
            ->setQuery($this->buildQuery($queryDsl))
            ->setFlags($this->jsonOutputFlags());

        return $this->streamResults($this->stub->Select($request));
    }

    /**
     * Executes a Query-DSL (JSON) update query.
     *
     * @param array<string, mixed>|string $queryDsl
     * @return iterable<int, array<string, mixed>>
     */
    public function update(array|string $queryDsl): iterable
    {
        $request = new UpdateRequest();
        $request->setDbName($this->requireDbName())
            ->setQuery($this->buildQuery($queryDsl))
            ->setFlags($this->jsonOutputFlags());

        return $this->streamResults($this->stub->Update($request));
    }

    /**
     * Executes a Query-DSL (JSON) delete query.
     *
     * @param array<string, mixed>|string $queryDsl
     * @return iterable<int, array<string, mixed>>
     */
    public function delete(array|string $queryDsl): iterable
    {
        $request = new DeleteRequest();
        $request->setDbName($this->requireDbName())
            ->setQuery($this->buildQuery($queryDsl))
            ->setFlags($this->jsonOutputFlags());

        return $this->streamResults($this->stub->Delete($request));
    }

    /**
     * Bulk item modification over a bidirectional stream, one item per message.
     *
     * @param iterable<array<string, mixed>|string> $items arrays or raw JSON strings
     * @param int $mode one of \Reindexer\Grpc\ModifyMode constants
     */
    public function modifyItems(string $nsName, iterable $items, int $mode = ModifyMode::UPSERT): void
    {
        $dbName = $this->requireDbName();
        $call = $this->stub->ModifyItem();

        try {
            foreach ($items as $item) {
                $request = new ModifyItemRequest();
                $request->setDbName($dbName)
                    ->setNsName($nsName)
                    ->setMode($mode)
                    ->setEncodingType(EncodingType::JSON)
                    ->setData($this->encodeJson($item));
                $call->write($request);
            }

            $call->writesDone();

            /** @var ErrorResponse|null $response */
            while (($response = $call->read()) !== null) {
                $this->throwIfError($response);
            }

            $this->assertStatusOk($call->getStatus());
        } catch (\Throwable $e) {
            $call->cancel();

            throw $e;
        }
    }

    public function beginTransaction(string $nsName): int
    {
        $request = new BeginTransactionRequest();
        $request->setDbName($this->requireDbName())->setNsName($nsName);

        $response = $this->unary($this->stub->BeginTransaction($request));
        $this->throwIfError($response->getStatus());

        return (int) $response->getId();
    }

    /**
     * @param iterable<array<string, mixed>|string> $items arrays or raw JSON strings
     * @param int $mode one of \Reindexer\Grpc\ModifyMode constants
     */
    public function addTxItems(int $transactionId, iterable $items, int $mode = ModifyMode::UPSERT): void
    {
        $call = $this->stub->AddTxItem();

        try {
            foreach ($items as $item) {
                $request = new AddTxItemRequest();
                $request->setId($transactionId)
                    ->setMode($mode)
                    ->setEncodingType(EncodingType::JSON)
                    ->setData($this->encodeJson($item));
                $call->write($request);
            }

            $call->writesDone();

            /** @var ErrorResponse|null $response */
            while (($response = $call->read()) !== null) {
                $this->throwIfError($response);
            }

            $this->assertStatusOk($call->getStatus());
        } catch (\Throwable $e) {
            $call->cancel();

            throw $e;
        }
    }

    /**
     * Sets the JSON schema of a namespace.
     *
     * @param array<string, mixed>|string $schema JSON schema as array or raw JSON string
     */
    public function setSchema(string $nsName, array|string $schema): void
    {
        $definition = new SchemaDefinition();
        $definition->setNsName($nsName)->setJsonData($this->encodeJson($schema));

        $request = new SetSchemaRequest();
        $request->setDbName($this->requireDbName())
            ->setSchemaDefinitionRequest($definition);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->SetSchema($request));
        $this->throwIfError($response);
    }

    /**
     * Returns the text of a .proto file describing the given namespaces.
     *
     * @param string[] $namespaces namespaces to include in the schema
     */
    public function getProtobufSchema(array $namespaces = []): string
    {
        $request = new GetProtobufSchemaRequest();
        $request->setDbName($this->requireDbName())->setNamespaces($namespaces);

        $response = $this->unary($this->stub->GetProtobufSchema($request));
        $this->throwIfError($response->getErrorResponse());

        return $response->getProto();
    }

    public function getMeta(string $nsName, string $key): string
    {
        $request = new GetMetaRequest();
        $request->setDbName($this->requireDbName())
            ->setMetadata($this->buildMetadata($nsName, $key));

        $response = $this->unary($this->stub->GetMeta($request));
        $this->throwIfError($response->getErrorResponse());

        return $response->getMetadata();
    }

    public function putMeta(string $nsName, string $key, string $value): void
    {
        $request = new PutMetaRequest();
        $request->setDbName($this->requireDbName())
            ->setMetadata($this->buildMetadata($nsName, $key, $value));

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->PutMeta($request));
        $this->throwIfError($response);
    }

    /**
     * @return string[] metadata keys of the namespace
     */
    public function enumMeta(string $nsName): array
    {
        $request = new EnumMetaRequest();
        $request->setDbName($this->requireDbName())->setNsName($nsName);

        $response = $this->unary($this->stub->EnumMeta($request));
        $this->throwIfError($response->getErrorResponse());

        return iterator_to_array($response->getKeys(), false);
    }

    public function deleteMeta(string $nsName, string $key): void
    {
        $request = new DeleteMetaRequest();
        $request->setDbName($this->requireDbName())
            ->setMetadata($this->buildMetadata($nsName, $key));

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->DeleteMeta($request));
        $this->throwIfError($response);
    }

    private function buildMetadata(string $nsName, string $key, string $value = ''): Metadata
    {
        $metadata = new Metadata();
        $metadata->setNsName($nsName)->setKey($key)->setValue($value);

        return $metadata;
    }

    public function commitTransaction(int $transactionId): void
    {
        $request = new CommitTransactionRequest();
        $request->setId($transactionId);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->CommitTransaction($request));
        $this->throwIfError($response);
    }

    public function rollbackTransaction(int $transactionId): void
    {
        $request = new RollbackTransactionRequest();
        $request->setId($transactionId);

        /** @var ErrorResponse $response */
        $response = $this->unary($this->stub->RollbackTransaction($request));
        $this->throwIfError($response);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildIndex(array $definition): Index
    {
        if (!isset($definition['name']) || $definition['name'] === '') {
            throw new GrpcException('Index definition requires a non-empty "name"');
        }

        $options = new IndexOptions();
        $options->setIsPk($definition['isPk'] ?? false)
            ->setIsArray($definition['isArray'] ?? false)
            ->setIsDense($definition['isDense'] ?? false)
            ->setIsSparse($definition['isSparse'] ?? false);

        $index = new Index();
        $index->setName($definition['name'])
            ->setJsonPaths($definition['jsonPaths'] ?? [$definition['name']])
            ->setIndexType($definition['indexType'] ?? 'hash')
            ->setFieldType($definition['fieldType'] ?? 'string')
            ->setOptions($options)
            ->setExpireAfter($definition['expireAfter'] ?? 0);

        return $index;
    }

    /**
     * @param array<string, mixed>|string $queryDsl
     */
    private function buildQuery(array|string $queryDsl): Query
    {
        $query = new Query();
        $query->setEncodingType(EncodingType::JSON)
            ->setData($this->encodeJson($queryDsl));

        return $query;
    }

    private function jsonOutputFlags(): OutputFlags
    {
        $flags = new OutputFlags();
        $flags->setEncodingType(EncodingType::JSON);

        return $flags;
    }

    /**
     * @param \Grpc\ServerStreamingCall $call
     * @return Generator<int, array<string, mixed>>
     */
    private function streamResults(object $call): Generator
    {
        $completed = false;

        try {
            foreach ($call->responses() as $response) {
                $this->throwIfError($response->getErrorResponse());

                $data = $response->getData();
                if ($data === '') {
                    continue;
                }

                foreach ($this->decodeJsonResults($data) as $item) {
                    yield $item;
                }
            }

            $this->assertStatusOk($call->getStatus());
            $completed = true;
        } finally {
            // An abandoned generator (early break) or an exception must not
            // leave the server-side stream running.
            if (!$completed) {
                $call->cancel();
            }
        }
    }

    /**
     * Decodes a JSON payload of QueryResultsResponse.data into a list of items.
     *
     * @return array<int, array<string, mixed>>
     */
    private function decodeJsonResults(string $data): array
    {
        try {
            $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new GrpcException(sprintf('Failed to decode JSON query results: %s', $e->getMessage()), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new GrpcException('Unexpected JSON query results payload: ' . get_debug_type($decoded));
        }

        if (array_is_list($decoded)) {
            return $decoded;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded['items'];
        }

        return [$decoded];
    }

    /**
     * @param array<string, mixed>|string $value
     */
    private function encodeJson(array|string $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param \Grpc\UnaryCall $call
     */
    private function unary(object $call): object
    {
        [$response, $status] = $call->wait();
        $this->assertStatusOk($status);

        if ($response === null) {
            throw new GrpcException('Empty response from Reindexer gRPC server');
        }

        return $response;
    }

    private function throwIfError(?ErrorResponse $error): void
    {
        if ($error !== null && $error->getCode() !== ErrorResponse\ErrorCode::errCodeOK) {
            throw GrpcException::fromErrorResponse($error->getCode(), $error->getWhat());
        }
    }

    private function assertStatusOk(object $status): void
    {
        if ((int) $status->code !== self::GRPC_STATUS_OK) {
            throw GrpcException::fromStatus((int) $status->code, (string) ($status->details ?? ''));
        }
    }

    private function requireDbName(): string
    {
        if ($this->dbName === '') {
            throw new GrpcException('Not connected: call connect($dbName) before other RPCs');
        }

        return $this->dbName;
    }
}
