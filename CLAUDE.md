# reindexer-client

PHP client for Reindexer with two transports: HTTP (Guzzle) and optional gRPC.

## Commands

All development runs in Docker (`docker-compose-tests.yml`: `php` = no grpc, `php-grpc` = ext-grpc, `reindexer` = server v5.15.0):

```bash
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/phpunit --testsuite Unit
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/phpunit --testsuite Feature      # needs reindexer service
docker compose -f docker-compose-tests.yml run --rm php-grpc vendor/bin/phpunit --testsuite GrpcFeature     # needs ext-grpc + server
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/infection --threads=max          # mutation, CI gate --min-msi=95
docker compose -f docker-compose-tests.yml run --rm -w /app php vendor/bin/php-cs-fixer fix                 # style (check: composer check-fix)
docker compose -f docker-compose-tests.yml run --rm php-grpc composer proto-gen                             # regenerate gRPC stubs
```

Benchmarks: `composer bench-seed` / `bench-load` / `bench` (run in `php-grpc`), see `docs/benchmarks.md`.

## Architecture

- `src/Reindexer/Client/` — `Api` (Guzzle wrapper) + `BaseApi`; returns `Reindexer\Response`.
- `src/Reindexer/Services/` — HTTP REST endpoints: `Database`, `Namespaces`, `Index`, `Item`, `Query`.
- `src/Reindexer/Entities/` — `Index`, `SdlQuery` (JSON-serializable via `Entity` base with `mapJsonFields`).
- `src/Reindexer/Enum/` — `FieldType`, `IndexType`, `CollateMode` backed string enums.
- `src/Reindexer/Transport/Grpc/GrpcClient.php` — high-level gRPC wrapper; `src/Reindexer/Exceptions/GrpcException.php`.
- `src/Grpc/Generated/` — protoc output from `proto/reindexer.proto`.

## Constraints

- **Releases: tag only a fully green master** — after merging, wait for ALL post-merge
  workflow runs (`Tests`, `Mutation`, `Lint`) to succeed on `master`, then tag that SHA.
  A green PR is not sufficient. Full checklist: [docs/RELEASING.md](docs/RELEASING.md).
- Never hand-edit `src/Grpc/Generated/` — edit `proto/reindexer.proto` and run `composer proto-gen`.
- The HTTP code path must not reference grpc classes: gRPC deps are `suggest`-only, `GrpcClient` has a runtime guard.
- Three test suites (Unit / Feature / GrpcFeature) + Infection on Unit; keep MSI above 95.
- Server version is pinned to `reindexer/reindexer:v5.15.0` (CI + compose); JSON encoding must keep `JSON_UNESCAPED_UNICODE`.
