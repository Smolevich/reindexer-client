<?php

/**
 * Aggregations: count/sum/min/max via SQL, facet via the Query-DSL endpoint.
 *
 * Run:
 *   REINDEXER_HOST=http://localhost:9088 php examples/aggregations.php
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
$ns = 'orders_agg';

$api = new Api($host, ['http_errors' => false, 'timeout' => 10]);

(new Database($api))->create($database);

$nsService = new Namespaces($api);
$nsService->setDatabase($database);
$nsService->drop($ns);

$index = fn (string $name, FieldType $ft, IndexType $it) => (new IndexEntity())
    ->setName($name)->setJsonPaths([$name])->setFieldType($ft)->setIndexType($it);
$nsService->create($ns, [
    $index('id', FieldType::INT, IndexType::HASH)->setIsPk(true),
    $index('status', FieldType::STRING, IndexType::HASH),
    $index('amount', FieldType::INT, IndexType::TREE),
]);

$items = new Item($api);
$items->setDatabase($database);
$items->setNamespace($ns);

$statuses = ['paid', 'pending', 'cancelled'];
for ($id = 1; $id <= 12; $id++) {
    $items->add(['id' => $id, 'status' => $statuses[$id % 3], 'amount' => $id * 100]);
}
echo "Loaded 12 orders into '$ns'" . PHP_EOL . PHP_EOL;

$query = new Query($api);
$query->setDatabase($database);

// Scalar aggregations via plain SQL: the result arrives in 'aggregations',
// 'items' stays empty. Values are always floats.
$agg = fn (string $sql): float => $query->createByHttpGet($sql)
    ->getDecodedResponseBody(true)['aggregations'][0]['value'];

echo 'count(*)                       = ' . $agg("SELECT count(*) FROM $ns") . PHP_EOL;
echo 'sum(amount)                    = ' . $agg("SELECT sum(amount) FROM $ns") . PHP_EOL;
echo 'min(amount)                    = ' . $agg("SELECT min(amount) FROM $ns") . PHP_EOL;
echo 'max(amount)                    = ' . $agg("SELECT max(amount) FROM $ns") . PHP_EOL;
echo 'sum(amount) WHERE status=paid  = ' . $agg("SELECT sum(amount) FROM $ns WHERE status = 'paid'") . PHP_EOL;

// Facet goes through the Query-DSL endpoint; the aggregations array is passed
// to the server as-is.
$body = $query->createSdlQueryByHttpPost([
    'namespace' => $ns,
    'aggregations' => [['type' => 'facet', 'fields' => ['status']]],
])->getDecodedResponseBody(true);
echo PHP_EOL . 'Orders per status (facet):' . PHP_EOL;
foreach ($body['aggregations'][0]['facets'] as $facet) {
    echo sprintf('  %-10s %d', $facet['values'][0], $facet['count']) . PHP_EOL;
}

$nsService->drop($ns);
echo PHP_EOL . "Dropped '$ns'" . PHP_EOL;
