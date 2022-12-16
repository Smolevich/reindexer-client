<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/common-part.php';

use Reindexer\Grpc\EncodingType;
use Reindexer\Grpc\ReindexerClient;

try {
    $request = new \Reindexer\Grpc\ConnectRequest([
        'url' => 'cproto://reindexer:16534/',
        'connectOpts' => new \Reindexer\Grpc\ConnectOptions([
            'expectedClusterID' => 1,
            'openNamespaces' => 1
        ]),
    ]);
    $client = new ReindexerClient('reindexer:16534', [
        'credentials' => \Grpc\ChannelCredentials::createInsecure(),
    ]);
    list($response, $error) = $client->connect($request)->wait();
    $request = new \Reindexer\Grpc\CreateDatabaseRequest(['dbName' => 'stats']);
    list($response, $error) = $client->createDatabase($request)->wait();
    $request = new Reindexer\Grpc\EnumDatabasesRequest();
    list($response, $error) = $client->enumDatabases($request)->wait();

    if ($response->getNames() !== null) {
        foreach ($response->getNames() as $name) {
            echo $name . PHP_EOL;
        }
    }
    $pbNamespace = new \Reindexer\Grpc\PBNamespace(['name' => 'statistics', 'dbName' => 'stats']);
    $request = new \Reindexer\Grpc\AddNamespaceRequest(['dbName' => 'stats', 'namespace' => $pbNamespace]);
    list($response, $error) = $client->addNamespace($request)->wait();

    $request = new \Reindexer\Grpc\OpenNamespaceRequest(['dbName' => 'stats']);
    list($response, $error) = $client->openNamespace($request)->wait();
    $request = new \Reindexer\Grpc\GetMetaRequest(['dbName' => 'stats']);
    list($response, $error) = $client->GetMeta($request)->wait();
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

    foreach ($users as $user) {
        $modifyRequest = new \Reindexer\Grpc\ModifyItemRequest([
            'dbName' => 'stats',
            'nsName' => 'statistics',
            'mode' => \Reindexer\Grpc\ModifyMode::UPSERT,
            'encodingType' => EncodingType::JSON,
            'data' => json_encode($user),
        ]);
        list($response, $error) = $client->ModifyItem($modifyRequest)->wait();
    }
} catch (\Throwable $e) {
    echo sprintf(
        'Error %s in file %s on line %s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
}
