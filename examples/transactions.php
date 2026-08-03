<?php

/**
 * HTTP transactions: begin -> add items (with serial() precepts) -> commit,
 * plus a rollback that leaves the namespace untouched.
 *
 * Run:
 *   REINDEXER_HOST=http://localhost:9088 php examples/transactions.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Reindexer\Client\Api;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Services\Database;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;
use Reindexer\Services\Transaction;

$host = getenv('REINDEXER_HOST') ?: 'http://localhost:9088';
$database = 'examples';
$ns = 'accounts_tx';

$api = new Api($host, ['http_errors' => false, 'timeout' => 10]);

(new Database($api))->create($database);

$nsService = new Namespaces($api);
$nsService->setDatabase($database);
$nsService->drop($ns);
$nsService->create($ns, [
    (new IndexEntity())->setName('id')->setJsonPaths(['id'])
        ->setFieldType(FieldType::INT)->setIndexType(IndexType::HASH)->setIsPk(true),
]);

$tx = new Transaction($api);
$tx->setDatabase($database);

$query = new Query($api);
$query->setDatabase($database);

// --- commit: both inserts become visible atomically ------------------------
$txId = $tx->begin($ns)->getDecodedResponseBody(true)['tx_id'];
echo "Began transaction $txId" . PHP_EOL;

// id=serial() lets the server assign auto-increment ids
$tx->addItem($txId, ['name' => 'alice', 'balance' => 100], ['id=serial()']);
$tx->addItem($txId, ['name' => 'bob', 'balance' => 200], ['id=serial()']);

$visible = $query->createByHttpGet("SELECT * FROM $ns")->getDecodedResponseBody(true)['items'];
echo 'Visible before commit: ' . count($visible) . ' items' . PHP_EOL;

$tx->commit($txId);
$visible = $query->createByHttpGet("SELECT * FROM $ns ORDER BY id")->getDecodedResponseBody(true)['items'];
echo 'Visible after commit:' . PHP_EOL;
foreach ($visible as $item) {
    echo "  #{$item['id']} {$item['name']} balance={$item['balance']}" . PHP_EOL;
}

// --- rollback: nothing changes ---------------------------------------------
$txId = $tx->begin($ns)->getDecodedResponseBody(true)['tx_id'];
$tx->updateItem($txId, ['id' => 1, 'name' => 'alice', 'balance' => 0]);
$tx->deleteItem($txId, ['id' => 2]);
$tx->rollback($txId);
echo PHP_EOL . "Rolled back transaction $txId" . PHP_EOL;

$visible = $query->createByHttpGet("SELECT * FROM $ns ORDER BY id")->getDecodedResponseBody(true)['items'];
echo 'After rollback (untouched):' . PHP_EOL;
foreach ($visible as $item) {
    echo "  #{$item['id']} {$item['name']} balance={$item['balance']}" . PHP_EOL;
}

$nsService->drop($ns);
echo PHP_EOL . 'Done.' . PHP_EOL;
