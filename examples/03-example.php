<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/common-part.php';

use Reindexer\Services\Database;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;

try {
    $users = [
        [
            'id' => 1, 'name' => 'John Doe'
        ],
        [
            'id' => 2, 'name' => 'Tom Soyer'
        ],
        [
            'id' => 3, 'name' => 'James Bond'
        ]
    ];
    $configData = file_get_contents(__DIR__ . '/config.json');
    $config = json_decode($configData, true);
    $configuration = new Configuration($config);

    $api = $configuration->getApi();
    $databaseName = $argv[1] ?? 'test';
    $namespaceName = $argv[2] ?? 'namespace';

    $itemService = new Item($api);
    $sqlService = new Query($api);
    $itemService->setNamespace($namespaceName);
    $itemService->setDatabase($databaseName);
    $sqlService->setDatabase($databaseName);

    foreach ($users as $user) {
        $response = $itemService->add($user);
    }

    $response = $sqlService->createByHttpGet('SELECT * from shupilkin_ns');
    var_dump($response->getResponseBody());
} catch (Exception $e) {
}
