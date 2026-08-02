[![CI](https://github.com/Smolevich/reindexer-client/actions/workflows/ci.yml/badge.svg)](https://github.com/Smolevich/reindexer-client/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Smolevich/reindexer-client/graph/badge.svg?token=61non7vjiK)](https://codecov.io/gh/Smolevich/reindexer-client)

# reindexer-client

PHP SDK for [Reindexer](https://github.com/Restream/reindexer) — a fast, embeddable in-memory document database with full-text and faceted search, written in C++. Single binary, SQL plus a JSON query DSL, secondary/array/composite indexes, aggregations, morphology-aware full-text out of the box.

Two transports:

- **HTTP** — covers the Reindexer REST API: databases, namespaces, indexes, items, SQL and Query-DSL queries, namespace metadata. Only requires Guzzle.
- **gRPC** (optional) — wrapper around stubs generated from `proto/reindexer.proto` (Reindexer v5.15.0): DDL, SQL/Query-DSL queries with server-streaming results, bulk item modification and transactions over bidirectional streams. Requires the `grpc` PHP extension; the HTTP transport works without it.

## Choosing a search engine: Elasticsearch, Algolia or Reindexer

A common fork when you need full-text + faceted search over millions of documents from PHP:

| | Elasticsearch / OpenSearch | Algolia | Reindexer |
|---|---|---|---|
| Deployment | JVM cluster to operate | SaaS, nothing to run | single C++ binary / Docker container |
| Data location | disk + page cache | their cloud | RAM (dataset must fit in memory) |
| Full-text search | yes, rich | yes | yes: stemming/morphology (en/ru), typo tolerance, ranking |
| Faceted search | yes | yes | yes, `facet` aggregations |
| Query language | JSON Query DSL | API calls | SQL + JSON DSL |
| Cost of ownership | cluster ops | per-record / per-search pricing | one process, MIT license |

Reindexer is the lightweight corner of this triangle. If your dataset fits in RAM on one machine — which on modern hardware means datasets in the hundreds of millions of documents, given enough memory — you get sub-millisecond queries with full-text and facets from a single process, without operating a cluster or paying per record. In [our benchmarks](docs/benchmarks.md) on a 6.9M-record catalog, point lookups over HTTP take ~140 μs and filtered+sorted queries ~400 μs end-to-end from PHP. If you need horizontal scaling far beyond one node's RAM, or a fully managed service, the other two columns exist for a reason.

### Full-text and faceted search in five lines

```php
// Full-text index — morphology works out of the box ("базы" matches "база данных")
$indexService->create(
    (new IndexEntity())->setName('body')->setJsonPaths(['body'])
        ->setFieldType(FieldType::STRING)->setIndexType(IndexType::TEXT),
    'mydb',
    'products'
);
$found = $queryService->createByHttpGet("SELECT * FROM products WHERE body = 'поиск'");

// Facets over a filtered result set (JSON DSL, aggregations pass through as-is)
$facets = $queryService->createSdlQueryByHttpPost([
    'namespace' => 'products',
    'filters' => [['field' => 'price', 'cond' => 'GE', 'value' => 100]],
    'aggregations' => [['type' => 'facet', 'fields' => ['brand']]],
])->getDecodedResponseBody(true)['aggregations'];
```

Both snippets are exercised against a real server in the integration suites (`IndexTypesTest` covers the morphology case, `QueryFeatureTest` the facet aggregation).

## Requirements

- PHP ^8.2 with `ext-json`
- For the gRPC transport additionally:
  - `ext-grpc` (`pecl install grpc`)
  - composer packages `grpc/grpc` and `google/protobuf` (listed in `suggest`, not `require`)

Tested against `reindexer/reindexer:v5.15.0` on PHP 8.2–8.5.

## Installation

```bash
composer require smolevich/reindexer-client
```

For the gRPC transport:

```bash
pecl install grpc
composer require grpc/grpc google/protobuf
```

Constructing `Reindexer\Transport\Grpc\GrpcClient` without these dependencies throws a `RuntimeException` explaining what is missing.

## Quick start: HTTP

```php
use Reindexer\Client\Api;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Services\Database;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;

$api = new Api('http://localhost:9088', ['http_errors' => false]);

// Create a database
$dbService = new Database($api);
$dbService->create('mydb');

// Create a namespace with a primary key index
$idIndex = (new IndexEntity())
    ->setName('id')
    ->setJsonPaths(['id'])
    ->setFieldType(FieldType::INT)
    ->setIndexType(IndexType::HASH)
    ->setIsPk(true);

$nsService = new Namespaces($api);
$nsService->setDatabase('mydb');
$nsService->create('users', [$idIndex]);

// Insert items
$itemService = new Item($api);
$itemService->setDatabase('mydb');
$itemService->setNamespace('users');
$itemService->add(['id' => 1, 'name' => 'John Doe']);
$itemService->add(['id' => 2, 'name' => 'Jane Roe']);

// Query
$queryService = new Query($api);
$queryService->setDatabase('mydb');
$response = $queryService->createByHttpGet('SELECT * FROM users WHERE id = 1');
$items = $response->getDecodedResponseBody(true)['items'];
```

The second `Api` constructor argument is passed to the Guzzle client as-is (timeouts, `http_errors`, auth, etc.). Every service method returns a `Reindexer\Response` exposing the HTTP code, raw and decoded body, headers and the underlying PSR-7 request. See `examples/` for runnable scripts.

## Quick start: gRPC

Reindexer serves gRPC on port 16534; in the official Docker image it is enabled out of the box (`docker run -p 16534:16534 reindexer/reindexer:v5.15.0`).

```php
use Reindexer\Transport\Grpc\GrpcClient;

$client = new GrpcClient('localhost:16534'); // plaintext channel by default

// Bind the channel to a database (required before any other call)
$client->createDatabase('mydb');
$client->connect('mydb');

// DDL
$client->openNamespace('users');
$client->addIndex('users', [
    'name' => 'id',
    'fieldType' => 'int',
    'indexType' => 'hash',
    'isPk' => true,
]);

// Bulk upsert over a bidirectional stream
$client->modifyItems('users', [
    ['id' => 1, 'name' => 'John Doe'],
    ['id' => 2, 'name' => 'Jane Roe'],
]);

// SQL query: results are streamed and yielded as a generator
foreach ($client->execSql('SELECT * FROM users ORDER BY id') as $item) {
    echo $item['name'], PHP_EOL;
}
```

Also available: `select()` / `update()` / `delete()` (Query-DSL, streaming results), `enumDatabases()` / `enumNamespaces()`, `updateIndex()` / `dropIndex()`, `truncateNamespace()` / `dropNamespace()`, and transactions (`beginTransaction()` → `addTxItems()` → `commitTransaction()` / `rollbackTransaction()`). Server errors are raised as `Reindexer\Exceptions\GrpcException` carrying the Reindexer error code or gRPC status. See `examples/grpc.php`.

Generated stubs live in `src/Grpc/Generated/` and are committed; regenerate them with `composer proto-gen` (requires `protoc` and `grpc_php_plugin`).

## Testing

Three PHPUnit suites: `Unit` (no server needed), `Feature` (HTTP integration) and `GrpcFeature` (gRPC integration). Everything runs in Docker via `docker-compose-tests.yml`, which starts a `reindexer/reindexer:v5.15.0` server and two PHP containers — `php` (no grpc extension) and `php-grpc` (with `ext-grpc`):

```bash
# Unit suite (no server required)
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/phpunit --testsuite Unit

# HTTP integration suite
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/phpunit --testsuite Feature

# gRPC integration suite
docker compose -f docker-compose-tests.yml run --rm php-grpc vendor/bin/phpunit --testsuite GrpcFeature

# Mutation testing (Infection, CI threshold --min-msi=95)
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/infection --threads=max

# Code style
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/php-cs-fixer fix --dry-run --diff
```

Integration suites read `REINDEXER_HOST` / `REINDEXER_GRPC_TARGET` from the environment (set in the compose file) and skip themselves when the variables are absent. CI runs the HTTP jobs on PHP 8.2–8.5 without the grpc extension, the gRPC jobs with it, and a mutation testing job.

## Benchmarks

`benchmarks/` contains a phpbench suite comparing the two transports on a snapshot of the public [Hugging Face Hub models catalog](https://huggingface.co/models) (2 958 985 records as of 2026-08-02, fetched via the [public API](https://huggingface.co/api/models); benchmarked at 20K, 2.96M and — with synthetic amplification — 6.92M records). Results and methodology: [docs/benchmarks.md](docs/benchmarks.md).

Summary: on this setup HTTP is faster in every scenario — gRPC adds ~60–90 μs of per-call overhead and its bidirectional bulk stream is synchronous in PHP. The practical benefit of the gRPC transport is streaming semantics (results are yielded as a generator without buffering the whole payload), not latency.

## API coverage

The SDK covers the everyday surface (databases, namespaces, indexes, items, SQL and DSL queries, aggregations, metadata over HTTP; DDL, streaming queries, bulk writes and transactions over gRPC), but not yet 100% of the server API: roughly 42% of REST endpoints and 20 of 27 gRPC RPCs as of v3.0.0. The full endpoint-by-endpoint audit — including known gaps such as HTTP transactions, precepts (`serial()`/`now()`), TTL/RTree index types and DSL builder drift — lives in [docs/api-coverage.md](docs/api-coverage.md) and doubles as the 3.1 roadmap.

## Upgrading from 2.x

Version 3.0.0 contains breaking changes.

| Change | 2.x | 3.0 |
|---|---|---|
| PHP requirement | `>= 8.0` | `^8.2` |
| `FieldType`, `IndexType`, `CollateMode` | classes with string constants | native backed string enums (`enum ...: string`) |
| `Entities\Index` setters | accepted `string` | accept the corresponding enum |
| PSR-4 autoload | `""` fallback root | `Reindexer\` => `src/Reindexer/` |

Index definition before/after:

```php
// 2.x
$index->setFieldType(FieldType::INT)      // string constant 'int'
    ->setIndexType(IndexType::HASH)       // string constant 'hash'
    ->setCollateMode(CollateMode::NONE);  // string constant 'none'

// 3.0 — same call sites, but the constants are enum cases now.
// Code that passed raw strings must switch to enum cases:
$index->setFieldType(FieldType::from('int'))  // or FieldType::INT
    ->setIndexType(IndexType::HASH)
    ->setCollateMode(CollateMode::NONE);
```

Code that used `FieldType::INT` etc. by name keeps compiling; code that compared against or passed raw strings (`'int'`, `'hash'`) must use `Enum::from($string)` or the enum case, and read the string value via `->value`.

## Contributing

Pull requests are welcome. Before submitting:

1. Run the Unit and Feature suites (see [Testing](#testing)).
2. Run `composer check-fix` (php-cs-fixer, `@PSR12` + strict types).
3. Do not edit files under `src/Grpc/Generated/` by hand — change `proto/reindexer.proto` and run `composer proto-gen`.

## License

[MIT](LICENSE)
