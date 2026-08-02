<?php

/**
 * Loads the seeded Hugging Face model catalog (NDJSON) into reindexer.
 *
 * Usage:
 *   php benchmarks/load.php --transport=http [--file=...] [--db=bench_db] [--ns=hf_models] [--batch=1000]
 *   php benchmarks/load.php --transport=grpc
 *
 * HTTP:  batched POST /items (multiple JSON documents per request body).
 * gRPC:  ModifyItem bidirectional stream, one stream per batch.
 *
 * The namespace is dropped and recreated with the full index schema on every run.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Reindexer\Benchmarks\Support\HfModels;
use Reindexer\Client\Api;
use Reindexer\Transport\Grpc\GrpcClient;

$options = getopt('', ['transport:', 'file::', 'db::', 'ns::', 'batch::']);
$transport = (string) ($options['transport'] ?? 'http');
$file = (string) ($options['file'] ?? HfModels::DEFAULT_DATA_FILE);
$db = (string) ($options['db'] ?? HfModels::DEFAULT_DB);
$ns = (string) ($options['ns'] ?? HfModels::DEFAULT_NS);
$batchSize = max(1, (int) ($options['batch'] ?? 1000));

if (!in_array($transport, ['http', 'grpc'], true)) {
    fwrite(STDERR, "--transport must be 'http' or 'grpc'\n");
    exit(1);
}

if (!is_file($file)) {
    fwrite(STDERR, "Data file $file not found. Run: composer bench-seed\n");
    exit(1);
}

$startedAt = microtime(true);
$loaded = 0;

if ($transport === 'http') {
    $host = (string) getenv('REINDEXER_HOST');
    if ($host === '') {
        fwrite(STDERR, "REINDEXER_HOST is not set\n");
        exit(1);
    }

    $api = new Api($host, ['http_errors' => false]);
    HfModels::ensureDatabaseHttp($api, $db);
    HfModels::recreateNamespaceHttp($api, $db, $ns);

    $batch = [];
    $flush = static function (array $batch) use ($api, $db, $ns): int {
        $response = $api->request(
            'POST',
            "/api/v1/db/$db/namespaces/$ns/items",
            implode("\n", $batch),
            ['Content-Type' => 'application/json']
        );
        if ($response->getCode() !== 200) {
            throw new RuntimeException(
                "Batch insert failed: HTTP {$response->getCode()} {$response->getResponseBody()}"
            );
        }
        $decoded = json_decode((string) $response->getResponseBody(), true);

        return (int) ($decoded['updated'] ?? 0);
    };

    foreach (HfModels::readNdjson($file) as $line) {
        $batch[] = $line;
        if (count($batch) >= $batchSize) {
            $loaded += $flush($batch);
            $batch = [];
        }
    }
    if ($batch !== []) {
        $loaded += $flush($batch);
    }
} else {
    $target = (string) getenv('REINDEXER_GRPC_TARGET');
    if ($target === '') {
        fwrite(STDERR, "REINDEXER_GRPC_TARGET is not set\n");
        exit(1);
    }

    $client = new GrpcClient($target);
    try {
        $client->connect($db);
    } catch (\Throwable) {
        $client->createDatabase($db);
        $client->connect($db);
    }
    HfModels::recreateNamespaceGrpc($client, $ns);

    $batch = [];
    foreach (HfModels::readNdjson($file) as $line) {
        $batch[] = $line;
        if (count($batch) >= $batchSize) {
            $client->modifyItems($ns, $batch);
            $loaded += count($batch);
            $batch = [];
        }
    }
    if ($batch !== []) {
        $client->modifyItems($ns, $batch);
        $loaded += count($batch);
    }
}

$elapsed = microtime(true) - $startedAt;
printf(
    "Loaded %d records into %s/%s via %s in %.1fs (%.0f rec/s, batch=%d)\n",
    $loaded,
    $db,
    $ns,
    $transport,
    $elapsed,
    $loaded / max($elapsed, 0.001),
    $batchSize
);
