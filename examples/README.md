# Examples

Self-contained runnable scripts. Each one reads the server address from the
environment (`REINDEXER_HOST` for HTTP, `REINDEXER_GRPC_TARGET` for gRPC),
creates its own namespace and drops it at the end, so reruns are idempotent.

| File | Shows | Transport |
|---|---|---|
| `01-example.php` | Create database, namespace with pk index, namespace metadata | HTTP |
| `02-example.php` | Drop a namespace | HTTP |
| `03-example.php` | Insert items, SQL SELECT/UPDATE | HTTP |
| `search-fulltext-facets.php` | Catalog search: full-text index with morphology, brand facets via aggregations DSL, filter + sort + limit | HTTP |
| `array-fields-tags.php` | Array index on tags, containment queries (`tags = 'x'`, `IN`), updating items with arrays | HTTP |
| `aggregations.php` | count/sum/min/max via SQL, facet via Query-DSL | HTTP |
| `transactions.php` | HTTP transactions: begin → add items with `serial()` precepts → commit; rollback leaving data untouched | HTTP |
| `hf-models-search.php` | End-to-end on a real dataset (100 Hugging Face models): load NDJSON, top-N by downloads, facet by library, name lookup | HTTP |
| `grpc.php` | gRPC basics: DDL, bulk upsert, streaming SQL, Query-DSL select, transaction | gRPC |
| `grpc-streaming.php` | gRPC: 1000-item bulk load from a generator, streaming read with constant memory, transaction commit/rollback | gRPC |

`01-example.php`–`03-example.php` take the endpoint from `config.json` instead
of the environment and expect database/namespace names as CLI arguments.

## Running with a local PHP

Start a server and point the scripts at it:

```bash
docker run -d --name reindexer -p 9088:9088 -p 16534:16534 reindexer/reindexer:v5.15.0
composer install

REINDEXER_HOST=http://localhost:9088 php examples/aggregations.php
REINDEXER_GRPC_TARGET=localhost:16534 php examples/grpc-streaming.php   # needs ext-grpc
```

## Running everything in Docker (no local PHP)

The host ports below (19088/26534) are arbitrary — pick any free ones.

```bash
# Server
docker run -d --name rx-examples -p 19088:9088 -p 26534:16534 reindexer/reindexer:v5.15.0

# Dependencies
docker run --rm -v "$PWD":/app -w /app composer:2 install

# HTTP examples: stock php image is enough
docker run --rm -v "$PWD":/app -w /app \
    --add-host=host.docker.internal:host-gateway \
    -e REINDEXER_HOST=http://host.docker.internal:19088 \
    php:8.4-cli php examples/search-fulltext-facets.php

# gRPC examples need ext-grpc — build the image from Dockerfile.grpc once
docker build -f Dockerfile.grpc -t reindexer-client-grpc .
docker run --rm -v "$PWD":/app -w /app \
    --add-host=host.docker.internal:host-gateway \
    -e REINDEXER_GRPC_TARGET=host.docker.internal:26534 \
    reindexer-client-grpc php examples/grpc-streaming.php

# Cleanup
docker rm -f rx-examples
```
