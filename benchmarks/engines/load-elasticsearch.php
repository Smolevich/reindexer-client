<?php

/**
 * Loads the seeded Hugging Face model catalog (NDJSON) into Elasticsearch.
 *
 * Usage:
 *   php benchmarks/engines/load-elasticsearch.php [--file=...] [--batch=5000]
 *                                                 [--limit=N] [--max-minutes=65]
 *
 * Drops and recreates the hf_models index (1 shard, 0 replicas, refresh
 * disabled during the load), then streams batched _bulk requests. If the load
 * exceeds --max-minutes it stops at the reached checkpoint and reports it.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use Reindexer\Benchmarks\Engines\Support\EngineDataset;
use Reindexer\Benchmarks\Support\HfModels;

const PROGRESS_STEP = 100_000;

$options = getopt('', ['file::', 'batch::', 'limit::', 'max-minutes::']);
$file = (string) ($options['file'] ?? EngineDataset::dataFile());
$batchSize = max(1, (int) ($options['batch'] ?? 5000));
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : PHP_INT_MAX;
$maxSeconds = (int) (60 * (float) ($options['max-minutes'] ?? 65));

$index = EngineDataset::COLLECTION;
$client = ClientBuilder::create()
    ->setHosts([EngineDataset::requireEnv('ELASTICSEARCH_HOST')])
    ->build();

try {
    $client->indices()->delete(['index' => $index]);
} catch (\Throwable) {
    // index did not exist
}

$client->indices()->create([
    'index' => $index,
    'body' => [
        'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            'refresh_interval' => '-1',
        ],
        'mappings' => [
            'properties' => [
                'id' => ['type' => 'keyword', 'fields' => ['text' => ['type' => 'text']]],
                'author' => ['type' => 'keyword', 'fields' => ['text' => ['type' => 'text']]],
                'downloads' => ['type' => 'long'],
                'likes' => ['type' => 'long'],
                'pipeline_tag' => ['type' => 'keyword'],
                'library_name' => ['type' => 'keyword'],
                'tags' => ['type' => 'keyword'],
                'created_ts' => ['type' => 'long'],
            ],
        ],
    ],
]);

$startedAt = microtime(true);
$loaded = 0;
$nextProgressAt = PROGRESS_STEP;
$stoppedEarly = false;

$flush = static function (array $body) use ($client, $index): void {
    $response = $client->bulk(['index' => $index, 'body' => $body])->asArray();
    if (($response['errors'] ?? false) === true) {
        foreach ($response['items'] as $item) {
            $error = $item['index']['error'] ?? null;
            if ($error !== null) {
                throw new RuntimeException('Bulk insert failed: ' . json_encode($error));
            }
        }
    }
};

$body = [];
foreach (HfModels::readNdjson($file, $limit) as $line) {
    $doc = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    $body[] = ['index' => ['_id' => $doc['id']]];
    $body[] = $doc;
    if (count($body) >= $batchSize * 2) {
        $flush($body);
        $body = [];
        $loaded += $batchSize;
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
if (!$stoppedEarly && $body !== []) {
    $flush($body);
    $loaded += intdiv(count($body), 2);
}

$client->indices()->putSettings(['index' => $index, 'body' => ['refresh_interval' => '1s']]);
$client->indices()->refresh(['index' => $index]);

$elapsed = microtime(true) - $startedAt;
printf(
    "Loaded %d records into %s in %.1fs (%.0f rec/s, batch=%d)%s\n",
    $loaded,
    $index,
    $elapsed,
    $loaded / max($elapsed, 0.001),
    $batchSize,
    $stoppedEarly ? ' — STOPPED at time cap, checkpoint only' : ''
);
