<?php

/**
 * Mini end-to-end on a real dataset: 100 models from the Hugging Face Hub
 * catalog (benchmarks/data/sample-100.ndjson, source: https://huggingface.co/api/models).
 *
 * Loads the sample, then: top by downloads among pipeline_tag='text-generation',
 * facet by library_name, model lookup by name.
 *
 * Run:
 *   REINDEXER_HOST=http://localhost:9088 php examples/hf-models-search.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Reindexer\Client\Api;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Services\Database;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;

$host = getenv('REINDEXER_HOST') ?: 'http://localhost:9088';
$database = 'examples';
$ns = 'hf_models';
$dataFile = __DIR__ . '/../benchmarks/data/sample-100.ndjson';

$api = new Api($host, ['http_errors' => false, 'timeout' => 10]);

(new Database($api))->create($database);

$nsService = new Namespaces($api);
$nsService->setDatabase($database);
$nsService->drop($ns);

$index = fn (string $name, FieldType $ft, IndexType $it) => (new IndexEntity())
    ->setName($name)->setJsonPaths([$name])->setFieldType($ft)->setIndexType($it);
$nsService->create($ns, [
    // 'id' in the dataset is the model name (string) — a string pk works too
    $index('id', FieldType::STRING, IndexType::HASH)->setIsPk(true),
    $index('pipeline_tag', FieldType::STRING, IndexType::HASH),
    $index('library_name', FieldType::STRING, IndexType::HASH),
    $index('downloads', FieldType::INT64, IndexType::TREE),
]);

$items = new Item($api);
$items->setDatabase($database);
$items->setNamespace($ns);

$loaded = 0;
foreach (new SplFileObject($dataFile) as $line) {
    $line = trim((string) $line);
    if ($line === '') {
        continue;
    }
    $items->add(json_decode($line, true, flags: JSON_THROW_ON_ERROR));
    $loaded++;
}
echo "Loaded $loaded models into '$ns'" . PHP_EOL;

$query = new Query($api);
$query->setDatabase($database);

// 1. Top-5 text-generation models by downloads.
$top = $query->createByHttpGet(
    "SELECT * FROM $ns WHERE pipeline_tag = 'text-generation' ORDER BY downloads DESC LIMIT 5"
)->getDecodedResponseBody(true);
echo PHP_EOL . 'Top-5 text-generation models by downloads:' . PHP_EOL;
foreach ($top['items'] as $item) {
    echo sprintf('  %10d  %s', $item['downloads'], $item['id']) . PHP_EOL;
}

// 2. Facet by library_name: which frameworks dominate the sample.
$body = $query->createSdlQueryByHttpPost([
    'namespace' => $ns,
    'aggregations' => [['type' => 'facet', 'fields' => ['library_name']]],
])->getDecodedResponseBody(true);
$facets = $body['aggregations'][0]['facets'];
usort($facets, fn (array $a, array $b) => $b['count'] <=> $a['count']);
echo PHP_EOL . 'Models per library:' . PHP_EOL;
foreach (array_slice($facets, 0, 5) as $facet) {
    echo sprintf('  %-25s %d', $facet['values'][0] ?: '(none)', $facet['count']) . PHP_EOL;
}

// 3. Lookup by model name: LIKE over the string pk (substring match).
$found = $query->createByHttpGet(
    "SELECT * FROM $ns WHERE id LIKE '%whisper%'"
)->getDecodedResponseBody(true);
echo PHP_EOL . "Models with 'whisper' in the name:" . PHP_EOL;
foreach ($found['items'] as $item) {
    echo sprintf('  %s (%s, %d downloads)', $item['id'], $item['pipeline_tag'], $item['downloads']) . PHP_EOL;
}

$nsService->drop($ns);
echo PHP_EOL . "Dropped '$ns'" . PHP_EOL;
