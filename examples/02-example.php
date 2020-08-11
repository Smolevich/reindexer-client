<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/common-part.php';

use Reindexer\Services\Namespaces;

try {
    $configData = file_get_contents(__DIR__ . '/config.json');
    $config = json_decode($configData, true);
    $configuration = new Configuration($config);
    $api = $configuration->getApi();
    $databaseName = $argv[1] ?? 'test';
    $namespaceName = $argv[2] ?? 'namespace';
    $namespaceService = new Namespaces($api);
    $namespaceService->setDatabase($databaseName);
    $response = $namespaceService->delete($namespaceName);

    if ($response->getCode() === 200) {
        echo sprintf('Namespace with name %s was deleted', $namespaceName);
    } else {
        echo sprintf('Error delete namespace: %s', $response->getResponseBody());
    }
} catch (\Throwable $e) {
    echo sprintf(
        'Error %s in file %s on line %s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
}
