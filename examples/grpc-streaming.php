<?php

/**
 * gRPC transport: bulk load 1000 items over a bidirectional stream
 * (modifyItems), stream a large SELECT through a generator with a running
 * aggregate (constant memory), transaction commit/rollback.
 *
 * Requires ext-grpc + composer packages grpc/grpc, google/protobuf.
 *
 * Run:
 *   REINDEXER_GRPC_TARGET=localhost:16534 php examples/grpc-streaming.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Reindexer\Exceptions\GrpcException;
use Reindexer\Transport\Grpc\GrpcClient;

$target = getenv('REINDEXER_GRPC_TARGET') ?: GrpcClient::DEFAULT_TARGET;
$database = 'examples_grpc';
$ns = 'events_stream';

$client = new GrpcClient($target);

try {
    $client->connect($database);
} catch (GrpcException) {
    $client->createDatabase($database);
    $client->connect($database);
}

// Idempotency: drop leftovers from a previous run, then recreate.
try {
    $client->dropNamespace($ns);
} catch (GrpcException) {
    // namespace did not exist
}
$client->openNamespace($ns);
$client->addIndex($ns, ['name' => 'id', 'fieldType' => 'int', 'indexType' => 'hash', 'isPk' => true]);
$client->addIndex($ns, ['name' => 'level', 'fieldType' => 'string', 'indexType' => 'hash']);
$client->addIndex($ns, ['name' => 'duration_ms', 'fieldType' => 'int', 'indexType' => 'tree']);

// 1. Bulk load: modifyItems accepts any iterable — feed it a generator so the
// payload is produced lazily while items go out over one bidirectional stream.
$events = function (int $count): Generator {
    $levels = ['debug', 'info', 'warn', 'error'];
    for ($id = 1; $id <= $count; $id++) {
        yield [
            'id' => $id,
            'level' => $levels[$id % 4],
            'duration_ms' => ($id * 7919) % 1000, // pseudo-random but reproducible
        ];
    }
};
$started = microtime(true);
$client->modifyItems($ns, $events(1000));
echo sprintf('Bulk-loaded 1000 events in %.0f ms', (microtime(true) - $started) * 1000) . PHP_EOL;

// 2. Streaming read: execSql returns a generator — items are yielded straight
// off the gRPC stream, nothing buffers the whole result set. Aggregate on the
// fly; peak memory stays flat regardless of result size.
$memBefore = memory_get_usage(true);
$count = 0;
$totalDuration = 0;
foreach ($client->execSql("SELECT * FROM $ns WHERE level = 'error'") as $item) {
    $count++;
    $totalDuration += $item['duration_ms'];
}
echo sprintf(
    "Streamed %d 'error' events, avg duration %.1f ms, memory delta %d bytes",
    $count,
    $totalDuration / $count,
    memory_get_usage(true) - $memBefore
) . PHP_EOL;

// 3a. Transaction, committed: both items become visible atomically.
$txId = $client->beginTransaction($ns);
$client->addTxItems($txId, [
    ['id' => 1001, 'level' => 'audit', 'duration_ms' => 1],
    ['id' => 1002, 'level' => 'audit', 'duration_ms' => 2],
]);
$client->commitTransaction($txId);
$audit = iterator_to_array($client->execSql("SELECT * FROM $ns WHERE level = 'audit'"), false);
echo sprintf('After commit: %d audit events', count($audit)) . PHP_EOL;

// 3b. Transaction, rolled back: the write never lands.
$txId = $client->beginTransaction($ns);
$client->addTxItems($txId, [['id' => 1003, 'level' => 'audit', 'duration_ms' => 3]]);
$client->rollbackTransaction($txId);
$audit = iterator_to_array($client->execSql("SELECT * FROM $ns WHERE level = 'audit'"), false);
echo sprintf('After rollback: still %d audit events', count($audit)) . PHP_EOL;

$client->dropNamespace($ns);
echo "Dropped '$ns'" . PHP_EOL;
