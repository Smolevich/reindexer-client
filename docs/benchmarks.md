# HTTP vs gRPC transport benchmarks

Benchmarks of the two client transports against a real reindexer server on a
public dataset: the Hugging Face Hub model catalog
(`https://huggingface.co/api/models`), 20 000 records, sorted by downloads.

Record shape: `id` (string PK, e.g. `google-bert/bert-base-uncased`), `author`,
`downloads`, `likes`, `pipeline_tag`, `library_name`, `tags` (string array),
`created_ts` (unix timestamp). Namespace `hf_models` indexes: `id` (string hash
PK), `downloads`/`likes`/`created_ts` (int tree), `pipeline_tag`/`library_name`
(string hash), `tags` (string array hash), `id+author` (composite fulltext).

## How to run

Everything runs inside Docker (`docker-compose-tests.yml`); no local PHP needed.
The `php-grpc` container has both transports (ext-grpc + Guzzle).

```bash
# 1. seed: download the catalog into benchmarks/data/models.ndjson (~20 s)
docker compose -f docker-compose-tests.yml run --rm php-grpc composer bench-seed

# 2. load: create bench_db/hf_models with the full index schema and load it
docker compose -f docker-compose-tests.yml run --rm php-grpc composer bench-load -- --transport=http
#    (or --transport=grpc; both load the same 20 000 records)

# 3. bench
docker compose -f docker-compose-tests.yml run --rm php-grpc composer bench
```

`benchmarks/data/` is git-ignored except for `sample-100.ndjson` (smoke data,
usable via `BENCH_DATA_FILE`/`BENCH_BULK_COUNT` env vars). Seeder options:
`--count=N`, `--out=FILE`.

## Environment

| Component | Value |
|---|---|
| Machine | Apple M4 Pro, 64 GB RAM, macOS (arm64) |
| Docker | 29.6.1, all services in one bridge network |
| Server | reindexer/reindexer:v5.15.0 (HTTP :9088, gRPC :16534) |
| Client | PHP 8.4.24 CLI (Debian), ext-grpc 1.83.0, grpc/grpc 1.57, Guzzle 7.9 |
| Harness | phpbench 1.7.0, xdebug/opcache off |

Dataset: 20 000 records seeded 2026-08-02. Query selectivity on that snapshot:
`tags IN ('llama')` — 1 364 rows, `downloads > 100000 AND
pipeline_tag='text-generation'` — 512 rows, `created_ts` within last 90 days —
3 824 rows. Every subject fully reads and JSON-decodes the result set on both
transports.

## Results

phpbench, 5 iterations per subject, warmup 1 (bulk: warmup 0, fresh namespace
per iteration). Time per operation; ratio = HTTP mean / gRPC mean.

| Scenario | revs×its | HTTP mean | HTTP mode | gRPC mean | gRPC mode | HTTP/gRPC |
|---|---|---|---|---|---|---|
| Bulk insert 5000 (HTTP: 10×500 POST; gRPC: one bidi stream) | 1×5 | 57.55 ms | 56.77 ms | 230.83 ms | 232.37 ms | 0.25 |
| Point select by PK, `LIMIT 1` | 200×5 | 146.5 μs | 146.0 μs | 226.2 μs | 212.3 μs | 0.65 |
| `downloads > 100000 AND pipeline_tag='text-generation' ORDER BY likes DESC LIMIT 100` | 50×5 | 399.3 μs | 401.9 μs | 481.9 μs | 479.6 μs | 0.83 |
| `tags IN ('llama') LIMIT 100` (array index) | 50×5 | 381.5 μs | 379.6 μs | 465.1 μs | 468.3 μs | 0.82 |
| `created_ts > now-90d ORDER BY created_ts DESC LIMIT 100` | 50×5 | 426.3 μs | 391.2 μs | 470.0 μs | 465.5 μs | 0.91 |
| Fulltext `"id+author" = 'bert'` / `'llama'`, `LIMIT 100` | 50×5 | 382.8 μs | 380.8 μs | 466.6 μs | 455.0 μs | 0.82 |

rstdev was within ±3% for most subjects; outliers: gRPC point select ±12.6%,
HTTP date range ±16.7%.

## Conclusions

On this setup the HTTP transport is faster in every scenario. Read queries show
a roughly constant per-call overhead on the gRPC side (~60–90 μs per request:
protobuf envelope plus ext-grpc call machinery vs a keep-alive Guzzle
connection), which matters most for point selects (ratio 0.65) and shrinks
toward parity as server-side query cost grows (date range 0.91). The largest
gap is bulk insert: the PHP ext-grpc bidirectional stream writes messages one
at a time synchronously, so streaming 5000 items takes 4× longer than 10
batched HTTP POSTs, and the gRPC transport currently gives no throughput
advantage for bulk loading from PHP. gRPC's practical benefit in this client is
streaming semantics (results are yielded as a generator without buffering the
whole payload), not latency.
