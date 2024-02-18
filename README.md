[![CI](https://github.com/Smolevich/reindexer-client/actions/workflows/ci.yml/badge.svg)](https://github.com/Smolevich/reindexer-client/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Smolevich/reindexer-client/graph/badge.svg?token=61non7vjiK)](https://codecov.io/gh/Smolevich/reindexer-client)
# PHP client for work with [reindexer](https://github.com/Restream/reindexer)

## Installation

```bash
composer require smolevich/reindexer-client
```

## Configuration file for library

```
{
  "endpoint": "http://localhost:9088",
  "client_config": {
    "http_errors": 0
  }
}
```
* endpoint - url of reindexer instance
* client_config - guzzle configuration settings for api client, now it is options for guzzle client, in future versions fields can be renamed

## Example of using

```php
    $apiClient = new Api($this->config['endpoint'], $this->config['client_config']);
    $dbService = new Database($apiClient);
    $response = $dbService->getList();
```
