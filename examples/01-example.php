<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/common-part.php';

use Reindexer\Client\Api;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Services\Database;
use Reindexer\Services\Namespaces;

try {
    $configData = file_get_contents(__DIR__ . '/config.json');
    $config = json_decode($configData, true);
    $configuration = new Configuration($config);
    $api = $configuration->getApi();
    $databaseName = $argv[1] ?? 'test';
    $namespaceName = $argv[2] ?? 'namespace';
    $dbService = new Database($api);
    $response = $dbService->getList();
    $existDatabases = $response->getDecodedResponseBody(true)['items'] ?? [];
    $namespaceService = new Namespaces($api);
    $namespaceService->setDatabase($databaseName);
    $response = $namespaceService->getList('asc');
    $existNamespaces = $response->getDecodedResponseBody(true)['items'] ?? [];

    if (!in_array($databaseName, $existDatabases)) {
        $response = $dbService->create($databaseName);
        echo sprintf('Database %s successfully created.'.PHP_EOL, $databaseName);
    }

    if (!array_search($namespaceName, array_column($existNamespaces, 'name'))) {
        $indexId = new IndexEntity();
        $indexId->setCollateMode('none')
            ->setName('id')
            ->setIsPk(true)
            ->setIndexType(\Reindexer\Enum\IndexType::HASH)
            ->setFieldType('int')
            ->setJsonPath('id')
            ->setIsDense(true)
        ;
        $response = $namespaceService->create($namespaceName, [$indexId]);
        echo sprintf('Response data: %s', $response->getResponseBody()) . PHP_EOL;
    }
} catch (Exception $e) {
    echo sprintf(
        'Error %s in file %s on line %s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
}
