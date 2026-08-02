<?php

/**
 * Array fields: an array index on tags, containment queries (tags = 'x'
 * matches items whose array CONTAINS 'x'), adding and updating items with
 * array values.
 *
 * Run:
 *   REINDEXER_HOST=http://localhost:9088 php examples/array-fields-tags.php
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
$ns = 'articles_tags';

$api = new Api($host, ['http_errors' => false, 'timeout' => 10]);

(new Database($api))->create($database);

$nsService = new Namespaces($api);
$nsService->setDatabase($database);
$nsService->drop($ns);

$nsService->create($ns, [
    (new IndexEntity())->setName('id')->setJsonPaths(['id'])
        ->setFieldType(FieldType::INT)->setIndexType(IndexType::HASH)->setIsPk(true),
    // setIsArray(true) makes the hash index cover every element of the array
    (new IndexEntity())->setName('tags')->setJsonPaths(['tags'])
        ->setFieldType(FieldType::STRING)->setIndexType(IndexType::HASH)->setIsArray(true),
]);

$items = new Item($api);
$items->setDatabase($database);
$items->setNamespace($ns);

$items->add(['id' => 1, 'title' => 'Postgres at scale', 'tags' => ['db', 'postgres', 'ops']]);
$items->add(['id' => 2, 'title' => 'PHP generators', 'tags' => ['php', 'streams']]);
$items->add(['id' => 3, 'title' => 'Reindexer vs Elastic', 'tags' => ['db', 'search', 'php']]);
$items->add(['id' => 4, 'title' => 'Empty-handed', 'tags' => []]);
echo "Loaded 4 articles into '$ns'" . PHP_EOL;

$query = new Query($api);
$query->setDatabase($database);

// Containment: equality on an array index means "array contains the value".
$found = $query->createByHttpGet("SELECT * FROM $ns WHERE tags = 'php' ORDER BY id")
    ->getDecodedResponseBody(true);
echo PHP_EOL . "tags contains 'php':" . PHP_EOL;
foreach ($found['items'] as $item) {
    echo sprintf('  #%d %s [%s]', $item['id'], $item['title'], implode(', ', $item['tags'])) . PHP_EOL;
}

// IN over array index: any of the listed values contained.
$found = $query->createByHttpGet("SELECT * FROM $ns WHERE tags IN ('search', 'ops') ORDER BY id")
    ->getDecodedResponseBody(true);
echo PHP_EOL . "tags contains 'search' or 'ops': ids " .
    implode(', ', array_column($found['items'], 'id')) . PHP_EOL;

// Update replaces the whole item (PUT), including its array field.
$items->update(['id' => 2, 'title' => 'PHP generators', 'tags' => ['php', 'streams', 'db']]);
$found = $query->createByHttpGet("SELECT * FROM $ns WHERE tags = 'db' ORDER BY id")
    ->getDecodedResponseBody(true);
echo PHP_EOL . "after adding 'db' to article #2, tags contains 'db': ids " .
    implode(', ', array_column($found['items'], 'id')) . PHP_EOL;

$nsService->drop($ns);
echo PHP_EOL . "Dropped '$ns'" . PHP_EOL;
