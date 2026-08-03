# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.0] - 2026-08-03

### Added

- HTTP transactions: new service `Reindexer\Services\Transaction` —
  `begin()` (returns `tx_id`), `addItem`/`updateItem`/`upsertItem`/`deleteItem`
  (with optional precepts), `sqlQuery()` (UPDATE/DELETE SQL inside the
  transaction), `deleteQuery()` (Query-DSL delete inside the transaction),
  `commit()`/`rollback()`. Mirrors the gRPC transaction flow for HTTP-only
  setups.
- Precepts (`id=serial()`, `updated_at=now()`) on every `Item` write method:
  `add`, `update`, `delete` gain an optional `array $precepts` parameter
  (sent as exploded `precepts=...` query pairs).
- `Item::upsert()` — `PATCH /items`, the upsert write mode.
- Index management: `Services\Index::update()` (`PUT /indexes`, in-place
  index update), `IndexType::RTREE`/`IndexType::TTL`, `FieldType::POINT`,
  new `Enum\RtreeType` (linear/quadratic/greene/rstar); `Entities\Index`
  gains `is_sparse`, `expire_after`, `rtree_type` and `config`
  (fulltext/float-vector config as a raw array) with fluent setters.
- Query-DSL writes over HTTP: `Query::updateSdlQueryByHttpPut()`
  (`PUT /query`, `update_fields`/`drop_fields`) and
  `Query::deleteSdlQueryByHttpDelete()` (`DELETE /query`).
- `Entities\SdlQuery` brought up to the current server DSL: new keys `type`,
  `merge_queries`, `drop_fields`, `update_fields`, `explain`; `req_total`
  now emits the server enum (`disabled`/`enabled`/`cached`, booleans are
  normalized); fluent setters for every field. Legacy `joined`/`merged`
  keys are kept but deprecated.
- gRPC: the remaining 7 RPCs are wrapped — `getMeta`/`putMeta`/`enumMeta`/
  `deleteMeta`, `setSchema`, `getProtobufSchema` and `addNamespace` (full
  namespace definition with an index list). All 27 proto RPCs are now
  exposed.
- `Namespaces::deleteMetaDataKey()` — `DELETE /metabykey/{key}`.
- `examples/transactions.php` — runnable HTTP transaction + precepts demo.

### Infrastructure

- Test suites grew from 349 to 460 tests (344 Unit, 73 Feature,
  43 GrpcFeature); Infection MSI 97% (gate `--min-msi=95`).
- REST endpoint coverage 42% → 67% directly (82% counting system
  namespaces), gRPC 74% → 100%; see `docs/api-coverage.md`.

## [3.0.0] - 2026-08-02

### Added

- gRPC transport: `Reindexer\Transport\Grpc\GrpcClient`, a wrapper around stubs
  generated from `proto/reindexer.proto` (Reindexer v5.15.0). Supports
  `connect`, database/namespace/index DDL, `execSql`/`select`/`update`/`delete`
  (server-streaming, results yielded as `iterable`), bulk `modifyItems` and
  `addTxItems` over bidirectional streams, and transactions
  (`beginTransaction`/`commitTransaction`/`rollbackTransaction`).
- `Reindexer\Exceptions\GrpcException` carrying Reindexer `ErrorResponse` codes
  and gRPC statuses.
- The gRPC transport is optional: `ext-grpc`, `grpc/grpc` and `google/protobuf`
  are `suggest`, not `require`. The HTTP transport works without them; a
  runtime guard throws a descriptive `RuntimeException` when they are missing.
- Committed generated stubs in `src/Grpc/Generated/` and a `composer proto-gen`
  script to regenerate them.
- phpbench benchmark suite (`benchmarks/`) comparing HTTP and gRPC transports
  on the Hugging Face Hub model catalog (20 000 records); composer scripts
  `bench-seed`, `bench-load`, `bench`. Results in `docs/benchmarks.md`.
- `examples/grpc.php` demonstrating the gRPC transport.

### Changed

- **BC break**: minimum PHP version raised from `>= 8.0` to `^8.2`.
- **BC break**: `FieldType`, `IndexType` and `CollateMode` are now native
  backed string enums; `Entities\Index` setters accept enum cases instead of
  strings.
- **BC break**: PSR-4 autoload changed from a `""` fallback to
  `Reindexer\` => `src/Reindexer/`.
- JSON payloads are encoded with `JSON_UNESCAPED_UNICODE` (Reindexer 5.x
  corrupted escaped surrogate pairs, e.g. emoji).
- `declare(strict_types=1)` and full property/return types across the codebase.
- Upgraded to PHPUnit 11 and Guzzle ^7.9.

### Fixed

- `Namespaces::getMetaDataKey` sent `PUT` instead of `GET`.
- `Query::createSdlQueryByHttpPost` double-encoded the Query-DSL payload.
- `Entity::parseValue` ignored protected properties, so `SdlQuery` serialized
  to `[]`.

### Infrastructure

- Test suites: 303 tests — 219 Unit, 51 Feature (HTTP integration),
  33 GrpcFeature (gRPC integration).
- Mutation testing with Infection: MSI 98%, CI threshold `--min-msi=95`.
- CI matrix: PHP 8.2–8.5 without `ext-grpc` (Unit + Feature), PHP 8.2/8.4 with
  `ext-grpc` (GrpcFeature), plus a mutation testing job.
- Reindexer server pinned to `reindexer/reindexer:v5.15.0` in CI and compose
  files; `Dockerfile` (PHP 8.4), `Dockerfile.grpc` (PHP 8.4 + `ext-grpc`) and
  `docker-compose-tests.yml` (`php` / `php-grpc` / `reindexer` services).

## [2.0.4] - 2024-02-18

## [2.0.3] - 2023-05-26

## [2.0.2] - 2023-03-18

## [2.0.1] - 2022-12-23

## [2.0.0] - 2022-04-06

- PHP 8 support, HTTP transport covering databases, namespaces, indexes,
  items, SQL and Query-DSL queries, namespace metadata. For details of 2.0.x
  patch releases see the git history.

## [1.0.x] - 2018-09-14 … 2021-03-09

- Initial HTTP client. See git history.

[3.0.0]: https://github.com/Smolevich/reindexer-client/compare/2.0.4...3.0.0
[2.0.4]: https://github.com/Smolevich/reindexer-client/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/Smolevich/reindexer-client/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/Smolevich/reindexer-client/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/Smolevich/reindexer-client/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/Smolevich/reindexer-client/compare/1.0.4...2.0.0
