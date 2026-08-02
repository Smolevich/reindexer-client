# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
