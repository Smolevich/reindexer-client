[![Build Status](https://travis-ci.org/Smolevich/reindexer-client.svg?branch=master)](https://travis-ci.org/Smolevich/reindexer-client)
[![Coverage Status](https://coveralls.io/repos/github/Smolevich/reindexer-client/badge.svg)](https://coveralls.io/github/Smolevich/reindexer-client)
# PHP client for work with [reindexer](https://github.com/Restream/reindexer)

## Installation

```bash
composer require smolevich/reindexeVr-client
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
* client_config - configuration settings for api client, now it is options for guzzle client, in future versions fields can be renamed

## Example of using

```php
    $apiClient = new Api($this->config['endpoint'], $this->config['client_config']);
    $dbService = new Database($apiClient);
    $response = $dbService->getList();
```
