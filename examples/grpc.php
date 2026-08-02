<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Reindexer\Exceptions\GrpcException;
use Reindexer\Transport\Grpc\GrpcClient;

// Requires ext-grpc + composer packages grpc/grpc, google/protobuf.
// Reindexer serves gRPC on port 16534 (enabled by default in the docker image).
$target = getenv('REINDEXER_GRPC_TARGET') ?: GrpcClient::DEFAULT_TARGET;
$databaseName = $argv[1] ?? 'test';
$namespaceName = $argv[2] ?? 'namespace_grpc';

try {
    $client = new GrpcClient($target);

    try {
        $client->connect($databaseName);
    } catch (GrpcException) {
        $client->createDatabase($databaseName);
        $client->connect($databaseName);
    }

    $client->openNamespace($namespaceName);
    $client->addIndex($namespaceName, [
        'name' => 'id',
        'fieldType' => 'int',
        'indexType' => 'hash',
        'isPk' => true,
    ]);
    $client->addIndex($namespaceName, [
        'name' => 'name',
        'fieldType' => 'string',
        'indexType' => 'hash',
    ]);

    // Bulk upsert over a bidirectional stream
    $client->modifyItems($namespaceName, [
        ['id' => 1, 'name' => 'John Doe'],
        ['id' => 2, 'name' => 'Tom Soyer'],
        ['id' => 3, 'name' => 'James Bond'],
    ]);

    // SQL select: results are streamed and yielded one item at a time
    foreach ($client->execSql(sprintf('SELECT * FROM %s ORDER BY id', $namespaceName)) as $item) {
        echo sprintf('id=%d name=%s', $item['id'], $item['name']) . PHP_EOL;
    }

    // Query-DSL select
    $found = iterator_to_array($client->select([
        'namespace' => $namespaceName,
        'filters' => [
            ['field' => 'name', 'cond' => 'EQ', 'value' => 'James Bond'],
        ],
    ]), false);
    echo sprintf('Query-DSL matched %d item(s)', count($found)) . PHP_EOL;

    // Transaction: add items atomically
    $txId = $client->beginTransaction($namespaceName);
    $client->addTxItems($txId, [
        ['id' => 4, 'name' => 'Sherlock Holmes'],
    ]);
    $client->commitTransaction($txId);

    $total = iterator_to_array($client->execSql(sprintf('SELECT * FROM %s', $namespaceName)), false);
    echo sprintf('Namespace %s now contains %d items', $namespaceName, count($total)) . PHP_EOL;

    $client->dropNamespace($namespaceName);
} catch (\Throwable $e) {
    echo sprintf(
        'Error %s in file %s on line %s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
}
