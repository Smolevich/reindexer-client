<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Reindexer\Client\Api;
use Reindexer\Services\Namespaces;

try {
    $configData = file_get_contents(__DIR__ . '/config.json');
    $config = json_decode($configData, true);
    $api = new Api($config['endpoint'], ['http_errors' => 0]);
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
} catch (Exception $e) {
    echo sprintf(
        'Error %s in file %s on line %s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
}
