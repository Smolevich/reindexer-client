# HTTP vs gRPC transport benchmarks

Benchmarks of the two client transports against a real reindexer server on a
snapshot of the public Hugging Face Hub models catalog, at three scales:
the full catalog (2.96M records), the maximum that fits in this machine's
Docker VM (6.92M), and a small 20K subset.

## Dataset

- Source: the public Hugging Face Hub models catalog —
  [huggingface.co/models](https://huggingface.co/models), fetched via the
  public API [huggingface.co/api/models](https://huggingface.co/api/models)
  with cursor pagination (`Link` header), sorted by downloads.
- Snapshot date: **2026-08-02**, cursor exhausted at **2 958 985 models** —
  the full public catalog on that date.
- Reproduce:
  ```bash
  docker compose -f docker-compose-tests.yml run --rm php-grpc \
      composer bench-seed -- --count=all --out=benchmarks/data/models-full.ndjson
  ```
  Anonymous API requests are rate-limited harder; seeding is faster with a
  read-only Hugging Face token in the `HF_TOKEN` env var. The full pull took
  ~38 min anonymously (~1 400 rec/s).
- Record shape: `id` (string PK, e.g. `google-bert/bert-base-uncased`),
  `author`, `downloads`, `likes`, `pipeline_tag`, `library_name`, `tags`
  (string array), `created_ts` (unix timestamp). A committed 100-record sample
  lives at [`benchmarks/data/sample-100.ndjson`](../benchmarks/data/sample-100.ndjson);
  the full files are git-ignored.
- Scales beyond the real catalog are produced by `benchmarks/amplify.php`:
  mutated copies of real records (id gets a `~n` suffix, author redrawn from a
  pool of real authors, downloads/likes ±20% noise, created_ts shifted back
  0–365 days, tags kept verbatim).

Namespace `hf_models` indexes: `id` (string hash PK), `downloads`/`likes`/
`created_ts` (int tree), `pipeline_tag`/`library_name` (string hash), `tags`
(string array hash), `id+author` (composite fulltext).

## How to run

Everything runs inside Docker (`docker-compose-tests.yml`); no local PHP
needed. The `php-grpc` container has both transports (ext-grpc + Guzzle).

```bash
# 1. seed: download the catalog (see Dataset above; use --count=20000 for a quick run)
docker compose -f docker-compose-tests.yml run --rm php-grpc composer bench-seed -- --count=all --out=benchmarks/data/models-full.ndjson

# 2. load: create bench_db/hf_models with the full index schema and load it
docker compose -f docker-compose-tests.yml run --rm php-grpc composer bench-load -- --transport=http --file=benchmarks/data/models-full.ndjson

# 3. bench
docker compose -f docker-compose-tests.yml run --rm php-grpc composer bench
```

The loader also supports `--transport=grpc`, `--batch`, and incremental
scale-up via `--append`/`--offset`/`--limit` (used together with
`amplify.php --skip-originals` for the memory-ceiling runs below).

## Environment

| Component | Value |
|---|---|
| Machine | Apple M4 Pro, 64 GB RAM, macOS (arm64) |
| Docker | Docker Desktop 29.6.1; VM: 6 CPUs, **15.6 GiB RAM** |
| Server | reindexer/reindexer:v5.15.0 — **amd64 image running under emulation** on the arm64 VM |
| Client | PHP 8.4.24 CLI (Debian, arm64), ext-grpc 1.83.0, grpc/grpc 1.57, Guzzle 7.9 |
| Harness | phpbench 1.7.0, xdebug/opcache off |

The amd64 emulation applies to the server binary only; absolute numbers would
differ on native hardware, but both transports talk to the same emulated
server, so the HTTP/gRPC comparison itself is unaffected.

Every subject fully reads and JSON-decodes the result set on both transports.
Filter/fulltext/facet subjects repeat a fixed query (fulltext alternates
'bert'/'llama'), so the server's query cache can serve part of the reps; point
select rotates through 1000 real PKs; bulk insert writes into a namespace
recreated before every iteration.

## Memory footprint (server RSS, `docker stats`)

| State | Records | RSS |
|---|---|---|
| Empty server, clean volume | 0 | 28 MiB |
| Full catalog loaded, before fulltext index | 2 958 985 | 1.88 GiB |
| Full catalog + fulltext index built (30 s build) | 2 958 985 | 9.64 GiB |
| + 2.96M synthetic copies | 5 917 970 | 12.19 GiB |
| + 1M more copies — **ceiling on this VM** | 6 917 970 | 14.87 GiB |

The composite fulltext index over `id`+`author` dominates: ~7.8 GiB of the
9.64 GiB at 2.96M records. The next 1M-record append step would exceed the
15.6 GiB VM limit, so 6 917 970 records is the maximum scale benchmarked here;
at that size the server stayed stable through repeated queries and the full
phpbench suite (RSS 14.67–14.87 GiB). 20M records with this schema would need
roughly 40–45 GiB RAM.

Load throughput (HTTP, batch=5000): 94 800 rec/s for the full catalog
(31.2 s for 2.96M). gRPC loader (batch=1000, measured on the 20K subset):
21 200 rec/s.

## Results

phpbench, 5 iterations per subject, warmup 1 (bulk: warmup 0, fresh namespace
per iteration). Time per operation; ratio = HTTP mean / gRPC mean.

### Full catalog — 2 958 985 records

| Scenario | revs×its | HTTP mean | HTTP mode | gRPC mean | gRPC mode | HTTP/gRPC |
|---|---|---|---|---|---|---|
| Bulk insert 5000 (HTTP: 10×500 POST; gRPC: one bidi stream) | 1×5 | 60.42 ms | 60.07 ms | 217.80 ms | 217.28 ms | 0.28 |
| Point select by PK, `LIMIT 1` | 200×5 | 144.5 μs | 144.9 μs | 214.9 μs | 207.4 μs | 0.67 |
| `downloads > 100000 AND pipeline_tag='text-generation' ORDER BY likes DESC LIMIT 100` | 50×5 | 412.2 μs | 407.3 μs | 481.6 μs | 477.8 μs | 0.86 |
| `tags IN ('llama') LIMIT 100` (array index) | 50×5 | 393.0 μs | 387.1 μs | 460.4 μs | 453.5 μs | 0.85 |
| `created_ts > now-90d ORDER BY created_ts DESC LIMIT 100` | 50×5 | 328.2 μs | 326.5 μs | 400.3 μs | 395.7 μs | 0.82 |
| Fulltext `"id+author" = 'bert'` / `'llama'`, `LIMIT 100` | 50×5 | 388.4 μs | 383.0 μs | 459.5 μs | 454.3 μs | 0.85 |
| Facet `FACET(pipeline_tag)` over the whole namespace | 10×5 | 207.5 ms | 207.0 ms | 210.5 ms | 210.4 ms | 0.99 |

rstdev ≤ ±3.4% except gRPC point select (±6.2%).

### Maximum scale — 6 917 970 records (2.96M real + 3.96M synthetic)

| Scenario | HTTP mean | gRPC mean | HTTP/gRPC |
|---|---|---|---|
| Bulk insert 5000 | 68.13 ms | 222.13 ms | 0.31 |
| Point select by PK | 142.8 μs | 202.2 μs | 0.71 |
| Filter + sort (`downloads`/`pipeline_tag`) | 407.4 μs | 481.2 μs | 0.85 |
| `tags IN ('llama')` | 407.3 μs | 469.3 μs | 0.87 |
| Date range + sort | 323.7 μs | 402.2 μs | 0.80 |
| Fulltext 'bert'/'llama' | 503.5 μs (±30.0%) | 510.2 μs | 0.99 |
| Facet `FACET(pipeline_tag)` | 536.6 ms | 537.2 ms | 1.00 |

### Small dataset — 20 000 records (top of the catalog by downloads)

| Scenario | HTTP mean | gRPC mean | HTTP/gRPC |
|---|---|---|---|
| Bulk insert 5000 | 57.55 ms | 230.83 ms | 0.25 |
| Point select by PK | 146.5 μs | 226.2 μs | 0.65 |
| Filter + sort | 399.3 μs | 481.9 μs | 0.83 |
| `tags IN ('llama')` | 381.5 μs | 465.1 μs | 0.82 |
| Date range + sort | 426.3 μs | 470.0 μs | 0.91 |
| Fulltext 'bert'/'llama' | 382.8 μs | 466.6 μs | 0.82 |

## Conclusions

Indexed query latency is essentially scale-independent from 20K to 6.9M
records: point select stays at ~143–147 μs (HTTP) and ~202–226 μs (gRPC),
filtered/sorted/array/fulltext queries at 320–510 μs on both transports.
The scan-bound facet aggregation grows linearly with namespace size (208 ms at
2.96M, 537 ms at 6.92M) and shows transport parity — at that cost the
transport no longer matters. HTTP is faster in every latency-bound scenario:
the gRPC side carries a roughly constant ~60–90 μs per-call overhead (protobuf
envelope plus ext-grpc call machinery vs a keep-alive Guzzle connection),
which matters most for point selects (ratio 0.67–0.71). The largest gap
remains bulk insert: the PHP ext-grpc bidirectional stream writes messages one
at a time synchronously, so streaming 5000 items takes 3–4× longer than 10
batched HTTP POSTs. gRPC's practical benefit in this client is streaming
semantics (results are yielded as a generator without buffering the whole
payload), not latency. Memory, not query speed, is the scaling limit for this
schema: the composite fulltext index costs ~2.6 KiB/record on top of
~0.65 KiB/record for data plus regular indexes.
