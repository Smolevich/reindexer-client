<?php

/**
 * Loads the seeded Hugging Face model catalog (NDJSON) into reindexer.
 *
 * Usage:
 *   php benchmarks/load.php --transport=http [--file=...] [--db=bench_db] [--ns=hf_models]
 *                           [--batch=5000] [--limit=N] [--offset=N] [--append]
 *   php benchmarks/load.php --transport=grpc
 *
 * HTTP:  batched POST /items (multiple JSON documents per request body).
 * gRPC:  ModifyItem bidirectional stream, one stream per batch.
 *
 * By default the namespace is dropped and recreated with the full index schema.
 * With --append the namespace is left as is and records are inserted on top —
 * used for incremental scale-up runs (combine with --offset/--limit).
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Reindexer\Benchmarks\Support\HfModels;
use Reindexer\Client\Api;
use Reindexer\Grpc\ModifyMode;
use Reindexer\Transport\Grpc\GrpcClient;

const PROGRESS_STEP = 100_000;

$options = getopt('', ['transport:', 'file::', 'db::', 'ns::', 'batch::', 'limit::', 'offset::', 'append']);
$transport = (string) ($options['transport'] ?? 'http');
$file = (string) ($options['file'] ?? HfModels::DEFAULT_DATA_FILE);
$db = (string) ($options['db'] ?? HfModels::DEFAULT_DB);
$ns = (string) ($options['ns'] ?? HfModels::DEFAULT_NS);
$batchSize = max(1, (int) ($options['batch'] ?? 5000));
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : PHP_INT_MAX;
$offset = max(0, (int) ($options['offset'] ?? 0));
$append = array_key_exists('append', $options);

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
$nextProgressAt = PROGRESS_STEP;

$progress = static function (int $loaded) use (&$nextProgressAt, $startedAt): void {
    if ($loaded >= $nextProgressAt) {
        $elapsed = microtime(true) - $startedAt;
        printf("loaded %d records (%.0fs, %.0f rec/s)\n", $loaded, $elapsed, $loaded / max($elapsed, 0.001));
        $nextProgressAt += PROGRESS_STEP;
    }
};

if ($transport === 'http') {
    $host = (string) getenv('REINDEXER_HOST');
    if ($host === '') {
        fwrite(STDERR, "REINDEXER_HOST is not set\n");
        exit(1);
    }

    // Generous timeout: large batches against a loaded server can be slow.
    $api = new Api($host, ['http_errors' => false, 'timeout' => 300, 'connect_timeout' => 10]);
    HfModels::ensureDatabaseHttp($api, $db);
    if (!$append) {
        HfModels::recreateNamespaceHttp($api, $db, $ns);
    }

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

    foreach (HfModels::readNdjson($file, $limit, $offset) as $line) {
        $batch[] = $line;
        if (count($batch) >= $batchSize) {
            $loaded += $flush($batch);
            $batch = [];
            $progress($loaded);
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
    if (!$append) {
        HfModels::recreateNamespaceGrpc($client, $ns);
    }

    $batch = [];
    foreach (HfModels::readNdjson($file, $limit, $offset) as $line) {
        $batch[] = $line;
        if (count($batch) >= $batchSize) {
            // INSERT keeps the write semantics symmetric with the HTTP loader
            // (batched POST /items), so transport numbers stay comparable.
            $client->modifyItems($ns, $batch, ModifyMode::INSERT);
            $loaded += count($batch);
            $batch = [];
            $progress($loaded);
        }
    }
    if ($batch !== []) {
        $client->modifyItems($ns, $batch, ModifyMode::INSERT);
        $loaded += count($batch);
    }
}

$elapsed = microtime(true) - $startedAt;
printf(
    "Loaded %d records into %s/%s via %s in %.1fs (%.0f rec/s, batch=%d%s%s)\n",
    $loaded,
    $db,
    $ns,
    $transport,
    $elapsed,
    $loaded / max($elapsed, 0.001),
    $batchSize,
    $offset > 0 ? ", offset=$offset" : '',
    $append ? ', append' : ''
);
