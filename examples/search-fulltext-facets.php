<?php

/**
 * Product catalog search: full-text index with morphology, facets over brands
 * (aggregations DSL), filter + sort + limit.
 *
 * Run:
 *   REINDEXER_HOST=http://localhost:9088 php examples/search-fulltext-facets.php
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
$ns = 'products_catalog';

$api = new Api($host, ['http_errors' => false, 'timeout' => 10]);

(new Database($api))->create($database); // 400 if it already exists — fine

$nsService = new Namespaces($api);
$nsService->setDatabase($database);
$nsService->drop($ns); // idempotency: start from a clean namespace

// Schema: int pk, hash index on brand (exact match, facets), tree index on
// price (range filters, sorting), full-text index on body (morphology).
$index = fn (string $name, FieldType $ft, IndexType $it) => (new IndexEntity())
    ->setName($name)->setJsonPaths([$name])->setFieldType($ft)->setIndexType($it);
$nsService->create($ns, [
    $index('id', FieldType::INT, IndexType::HASH)->setIsPk(true),
    $index('brand', FieldType::STRING, IndexType::HASH),
    $index('price', FieldType::INT, IndexType::TREE),
    $index('body', FieldType::STRING, IndexType::TEXT),
]);

$items = new Item($api);
$items->setDatabase($database);
$items->setNamespace($ns);

$brands = ['nokia', 'siemens', 'philips', 'bosch'];
$adjectives = ['compact', 'wireless', 'industrial', 'portable', 'digital'];
$kinds = ['phone', 'router', 'vacuum cleaner', 'drill', 'sensor'];
for ($id = 1; $id <= 20; $id++) {
    $items->add([
        'id' => $id,
        'brand' => $brands[$id % count($brands)],
        'price' => 50 + $id * 25,
        'body' => sprintf(
            '%s %s with two year warranty',
            $adjectives[$id % count($adjectives)],
            $kinds[$id % count($kinds)]
        ),
    ]);
}
echo "Loaded 20 products into '$ns'" . PHP_EOL;

$query = new Query($api);
$query->setDatabase($database);

// 1. Full-text search. The query word is plural ("routers"), documents contain
// the singular — the stemmer matches it anyway.
$found = $query->createByHttpGet("SELECT * FROM $ns WHERE body = 'routers'")
    ->getDecodedResponseBody(true);
echo PHP_EOL . "Full-text 'routers' (documents say 'router'): " . count($found['items']) . ' hits' . PHP_EOL;
foreach ($found['items'] as $item) {
    echo sprintf('  #%d %-8s %s', $item['id'], $item['brand'], $item['body']) . PHP_EOL;
}

// 2. Facets by brand over a filtered set — aggregations go through the
// Query-DSL endpoint and are passed to the server as-is.
$body = $query->createSdlQueryByHttpPost([
    'namespace' => $ns,
    'filters' => [['field' => 'price', 'cond' => 'GE', 'value' => 300]],
    'aggregations' => [['type' => 'facet', 'fields' => ['brand']]],
])->getDecodedResponseBody(true);
echo PHP_EOL . 'Brand facets for price >= 300:' . PHP_EOL;
foreach ($body['aggregations'][0]['facets'] as $facet) {
    echo sprintf('  %-8s %d', $facet['values'][0], $facet['count']) . PHP_EOL;
}

// 3. Filter + sort + limit in plain SQL.
$top = $query->createByHttpGet(
    "SELECT * FROM $ns WHERE brand = 'nokia' ORDER BY price DESC LIMIT 3"
)->getDecodedResponseBody(true);
echo PHP_EOL . 'Top-3 nokia by price:' . PHP_EOL;
foreach ($top['items'] as $item) {
    echo sprintf('  #%-2d price=%d %s', $item['id'], $item['price'], $item['body']) . PHP_EOL;
}

$nsService->drop($ns);
echo PHP_EOL . "Dropped '$ns'" . PHP_EOL;
