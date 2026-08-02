<?php

/**
 * Loads the seeded Hugging Face model catalog (NDJSON) into Meilisearch.
 *
 * Usage:
 *   php benchmarks/engines/load-meilisearch.php [--file=...] [--batch=100000]
 *                                               [--limit=N] [--max-minutes=65]
 *
 * Meilisearch indexes asynchronously: documents are enqueued as tasks and the
 * engine merges consecutive addition tasks into batches. The loader enqueues
 * all batches, then waits until the last task completes; the reported time is
 * enqueue + indexing (i.e. until the data is actually searchable).
 *
 * Documents get a synthetic `uid` primary key (md5 of the real id, see
 * EngineDataset::uid()) because Meilisearch primary keys only allow
 * [a-zA-Z0-9_-]; the real id stays in the searchable `id` field. If indexing
 * exceeds --max-minutes the loader cancels pending tasks and reports the
 * checkpoint document count.
 */

declare(strict_types=1);

ini_set('memory_limit', '1G');

require __DIR__ . '/../../vendor/autoload.php';

use Meilisearch\Client;
use Meilisearch\Contracts\CancelTasksQuery;
use Reindexer\Benchmarks\Engines\Support\EngineDataset;
use Reindexer\Benchmarks\Support\HfModels;

const PROGRESS_STEP = 100_000;

$options = getopt('', ['file::', 'batch::', 'limit::', 'max-minutes::']);
$file = (string) ($options['file'] ?? EngineDataset::dataFile());
$batchSize = max(1, (int) ($options['batch'] ?? 100_000));
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : PHP_INT_MAX;
$maxSeconds = (int) (60 * (float) ($options['max-minutes'] ?? 65));

$client = new Client(
    EngineDataset::requireEnv('MEILISEARCH_HOST'),
    EngineDataset::requireEnv('MEILISEARCH_API_KEY')
);

$collection = EngineDataset::COLLECTION;

try {
    $client->waitForTask($client->deleteIndex($collection)['taskUid'], 300_000);
} catch (\Throwable) {
    // index did not exist
}

$client->waitForTask($client->createIndex($collection, ['primaryKey' => 'uid'])['taskUid'], 300_000);

$index = $client->index($collection);
// Filterable/sortable/searchable attributes must be configured before the
// documents arrive, otherwise Meilisearch reindexes everything again.
$settingsTask = $index->updateSettings([
    'searchableAttributes' => ['id', 'author'],
    'filterableAttributes' => ['downloads', 'pipeline_tag', 'tags', 'created_ts'],
    'sortableAttributes' => ['likes', 'created_ts'],
]);
$client->waitForTask($settingsTask['taskUid'], 300_000);

$startedAt = microtime(true);
$enqueued = 0;
$lastTaskUid = null;

$flush = static function (array $lines) use ($index): int {
    $task = $index->addDocumentsNdjson(implode("\n", $lines), 'uid');

    return (int) $task['taskUid'];
};

$batch = [];
foreach (HfModels::readNdjson($file, $limit) as $line) {
    $doc = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    $doc['uid'] = EngineDataset::uid((string) $doc['id']);
    $batch[] = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (count($batch) >= $batchSize) {
        $lastTaskUid = $flush($batch);
        $enqueued += count($batch);
        $batch = [];
        printf("enqueued %d records (%.0fs)\n", $enqueued, microtime(true) - $startedAt);
    }
}
if ($batch !== []) {
    $lastTaskUid = $flush($batch);
    $enqueued += count($batch);
}
printf("all %d records enqueued (%.0fs), waiting for indexing...\n", $enqueued, microtime(true) - $startedAt);

$stoppedEarly = false;
$status = 'enqueued';
while (true) {
    $task = $client->getTask((int) $lastTaskUid);
    $status = (string) $task['status'];
    if (in_array($status, ['succeeded', 'failed', 'canceled'], true)) {
        break;
    }
    if (microtime(true) - $startedAt > $maxSeconds) {
        $stoppedEarly = true;
        try {
            $client->cancelTasks((new CancelTasksQuery())->setStatuses(['enqueued', 'processing']));
        } catch (\Throwable) {
            // best effort
        }
        break;
    }
    sleep(5);
}

if ($status === 'failed') {
    $task = $client->getTask((int) $lastTaskUid);
    throw new RuntimeException('Indexing failed: ' . json_encode($task['error'] ?? $task));
}

$elapsed = microtime(true) - $startedAt;
$stats = $index->stats();
printf(
    "Loaded %d records into %s in %.1fs (%.0f rec/s, batch=%d, last task %s)%s\n",
    (int) $stats['numberOfDocuments'],
    $collection,
    $elapsed,
    ((int) $stats['numberOfDocuments']) / max($elapsed, 0.001),
    $batchSize,
    $status,
    $stoppedEarly ? ' — STOPPED at time cap, checkpoint only' : ''
);
