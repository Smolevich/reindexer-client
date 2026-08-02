<?php

/**
 * Loads the seeded Hugging Face model catalog (NDJSON) into Typesense.
 *
 * Usage:
 *   php benchmarks/engines/load-typesense.php [--file=...] [--batch=10000]
 *                                             [--limit=N] [--max-minutes=65]
 *
 * Drops and recreates the hf_models collection, then streams batched
 * /documents/import requests (JSONL). Documents get a synthetic `id` (md5 of
 * the real id, see EngineDataset::uid()) because Typesense ids must be
 * URL-safe; the real id is kept in the searchable `model_id` field. If the
 * load exceeds --max-minutes it stops at the reached checkpoint.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Reindexer\Benchmarks\Engines\Support\EngineDataset;
use Reindexer\Benchmarks\Support\HfModels;
use Typesense\Client;

const PROGRESS_STEP = 100_000;

$options = getopt('', ['file::', 'batch::', 'limit::', 'max-minutes::']);
$file = (string) ($options['file'] ?? EngineDataset::dataFile());
$batchSize = max(1, (int) ($options['batch'] ?? 10000));
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : PHP_INT_MAX;
$maxSeconds = (int) (60 * (float) ($options['max-minutes'] ?? 65));

$host = parse_url(EngineDataset::requireEnv('TYPESENSE_HOST'));
$client = new Client([
    'api_key' => EngineDataset::requireEnv('TYPESENSE_API_KEY'),
    'nodes' => [[
        'host' => $host['host'],
        'port' => $host['port'] ?? 8108,
        'protocol' => $host['scheme'] ?? 'http',
    ]],
    'connection_timeout_seconds' => 300,
]);

$collection = EngineDataset::COLLECTION;

try {
    $client->collections[$collection]->delete();
} catch (\Throwable) {
    // collection did not exist
}

$client->collections->create([
    'name' => $collection,
    'fields' => [
        ['name' => 'model_id', 'type' => 'string'],
        ['name' => 'author', 'type' => 'string'],
        ['name' => 'downloads', 'type' => 'int64'],
        ['name' => 'likes', 'type' => 'int64'],
        ['name' => 'pipeline_tag', 'type' => 'string', 'facet' => true, 'optional' => true],
        ['name' => 'library_name', 'type' => 'string', 'facet' => true, 'optional' => true],
        ['name' => 'tags', 'type' => 'string[]', 'facet' => true, 'optional' => true],
        ['name' => 'created_ts', 'type' => 'int64'],
    ],
]);

$startedAt = microtime(true);
$loaded = 0;
$nextProgressAt = PROGRESS_STEP;
$stoppedEarly = false;

$flush = static function (array $lines) use ($client, $collection): int {
    $response = (string) $client->collections[$collection]->documents->import(
        implode("\n", $lines),
        ['action' => 'create']
    );
    $ok = substr_count($response, '"success":true');
    if ($ok !== count($lines)) {
        $firstError = null;
        foreach (explode("\n", $response) as $lineResult) {
            if (!str_contains($lineResult, '"success":true')) {
                $firstError = $lineResult;
                break;
            }
        }
        throw new RuntimeException(sprintf(
            'Import batch failed: %d/%d ok, first error: %s',
            $ok,
            count($lines),
            (string) $firstError
        ));
    }

    return $ok;
};

$batch = [];
foreach (HfModels::readNdjson($file, $limit) as $line) {
    $doc = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    $doc['model_id'] = (string) $doc['id'];
    $doc['id'] = EngineDataset::uid($doc['model_id']);
    // Typesense rejects null values for typed fields; drop them (the fields
    // are declared optional in the schema).
    $batch[] = json_encode(
        array_filter($doc, static fn ($v) => $v !== null),
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    if (count($batch) >= $batchSize) {
        $loaded += $flush($batch);
        $batch = [];
        if ($loaded >= $nextProgressAt) {
            $elapsed = microtime(true) - $startedAt;
            printf("loaded %d records (%.0fs, %.0f rec/s)\n", $loaded, $elapsed, $loaded / max($elapsed, 0.001));
            $nextProgressAt += PROGRESS_STEP;
        }
        if (microtime(true) - $startedAt > $maxSeconds) {
            $stoppedEarly = true;
            break;
        }
    }
}
if (!$stoppedEarly && $batch !== []) {
    $loaded += $flush($batch);
}

$elapsed = microtime(true) - $startedAt;
printf(
    "Loaded %d records into %s in %.1fs (%.0f rec/s, batch=%d)%s\n",
    $loaded,
    $collection,
    $elapsed,
    $loaded / max($elapsed, 0.001),
    $batchSize,
    $stoppedEarly ? ' — STOPPED at time cap, checkpoint only' : ''
);
