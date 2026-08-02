# Engine comparison: Reindexer vs Elasticsearch vs Typesense vs Meilisearch

Same dataset, same machine, same day, same six query scenarios — four search
engines loaded one at a time and measured with phpbench through their official
PHP clients. The Reindexer transport-level benchmarks (HTTP vs gRPC) live in
[benchmarks.md](benchmarks.md); this document compares Reindexer's HTTP
transport against the other engines.

## Dataset

The full public Hugging Face Hub models catalog — **2 958 985 records** —
described in detail in the [Dataset section of benchmarks.md](benchmarks.md#dataset)
(snapshot 2026-08-02, `benchmarks/data/models-full.ndjson`, ~855 MB NDJSON).

## Methodology

- **One engine at a time.** Every engine is started on a clean volume, loaded,
  warmed, benchmarked, measured for memory and shut down before the next one
  starts. Nothing else runs on the Docker VM (15.6 GiB) during a run, so the
  RSS numbers are directly comparable.
- **Same session.** All numbers in this document — including the Reindexer
  ones — were measured in a single session on 2026-08-02 on the same machine.
  The Reindexer HTTP subjects were re-run, not copied from older tables.
- **Official images, pinned versions** (see the table below and
  [`benchmarks/docker-compose-engines.yml`](../benchmarks/docker-compose-engines.yml)).
  Elasticsearch runs single-node with a capped 4 GiB heap
  (`-Xms4g -Xmx4g`), security disabled.
- **Official PHP clients** (require-dev), all queries measured end-to-end from
  PHP including response decoding: every subject fully reads and decodes the
  result payload.
- **phpbench** for the latency scenarios (5 iterations, warmup 1; revs per
  subject in the tables). Bulk load is a one-shot operation timed by the
  loader scripts themselves.
- **⚠️ Reindexer runs under CPU emulation, the other engines do not.**
  The host is an arm64 Mac; `reindexer/reindexer:v5.15.0` is only published
  for amd64 and runs under qemu/Rosetta emulation inside the Docker VM, while
  `elasticsearch`, `typesense` and `getmeili/meilisearch` all ship native
  arm64 images. **The three competitors therefore have a hardware advantage
  in every table below. This is not corrected for or normalized away** — on
  native hardware Reindexer's absolute numbers would be better than shown
  here. Keep this in mind before reading any latency table.
- **Document ids.** Real HF ids contain `/` and `.` (e.g.
  `google-bert/bert-base-uncased`). Meilisearch primary keys only allow
  `[a-zA-Z0-9_-]` and Typesense ids must be URL-safe, so those two engines get
  a synthetic `md5(id)` document key; the real id stays in a searchable field
  (`id` for Meilisearch, `model_id` for Typesense). Point lookups compute the
  md5 of the wanted id first — same "fetch one document by key" semantics.
  Reindexer and Elasticsearch use the original id as the key. All engines
  look up the same 1000 documents (the first 1000 records of the dataset).
- **Algolia is excluded**: it is a SaaS whose latency would be network-bound
  (methodologically incomparable to local engines) and its free tier caps at
  10K records vs our 2.96M.

### Environment

| Component | Value |
|---|---|
| Machine | Apple M4 Pro, 64 GB RAM, macOS (arm64) |
| Docker | Docker Desktop 29.6.1; VM: 6 CPUs, **15.6 GiB RAM** |
| Reindexer | reindexer/reindexer:v5.15.0 — **amd64, emulated** |
| Elasticsearch | elasticsearch:9.4.4 — arm64 native, single node, 4 GiB heap |
| Typesense | typesense/typesense:30.2 — arm64 native |
| Meilisearch | getmeili/meilisearch:v1.51.0 — arm64 native |
| PHP clients | elasticsearch/elasticsearch 9.4.0, typesense/typesense-php 6.0.0, meilisearch/meilisearch-php 1.16.1, this repo's Reindexer client |
| Harness | PHP 8.4 CLI (Debian, arm64), phpbench 1.7.0, xdebug/opcache off |

### Scenario mapping

The six scenarios from the Reindexer benchmark, expressed idiomatically per
engine (see `benchmarks/engines/*Bench.php`, parameters shared via
`EngineDataset`):

| Scenario | Reindexer (SQL over HTTP) | Elasticsearch | Typesense | Meilisearch |
|---|---|---|---|---|
| Point lookup ×1000 | `WHERE id = ? LIMIT 1` | GET `/{index}/_doc/{id}` | GET document by id | GET document by pk |
| Filter + sort | `downloads > 100000 AND pipeline_tag = 'text-generation' ORDER BY likes DESC LIMIT 100` | bool filter (range + term) + sort | `filter_by` + `sort_by` | `filter` + `sort` |
| Array contains | `tags IN ('llama') LIMIT 100` | term on keyword array | `filter_by: tags:=llama` | `filter: tags = "llama"` |
| Date range + sort | `created_ts > now-90d ORDER BY created_ts DESC LIMIT 100` | range + sort | `filter_by` + `sort_by` | `filter` + `sort` |
| Fulltext 'bert'/'llama' | composite `"id+author"` fulltext index | `multi_match` on `id.text`,`author.text` | `query_by: model_id,author` | search, `searchableAttributes: [id, author]` |
| Facet on filtered set | `SELECT FACET(pipeline_tag) WHERE downloads > 100000` | terms aggregation + range filter | `facet_by` + `filter_by` | `facets` + `filter` |

Result limit is 100 everywhere; the facet runs over the records with
`downloads > 100000` (2 277 models — the catalog is heavy-tailed, so this
filtered facet is a small-set aggregation; the whole-namespace facet number
for Reindexer is reported separately below). Note the small semantic differences that
cannot be fully aligned: Reindexer/Elasticsearch/Typesense filters are exact,
while Meilisearch's fulltext is typo-tolerant by default; each engine uses its
own tokenizer, so "fulltext 'bert'" does not return byte-identical result
sets across engines — the scenario compares each engine's idiomatic search
path, not identical result semantics.

## Bulk load and memory — 2 958 985 records

Loader scripts, wall time from first request until the data is searchable.
RSS is the engine container's `docker stats` memory after load / after the
query suite (client containers excluded).

| Engine | Load time | Throughput | RSS empty | RSS after load | RSS after benches |
|---|---|---|---|---|---|
| Reindexer v5.15.0 *(emulated)* | 32.1 s + 28.9 s fulltext build | 92 100 rec/s | 28 MiB | 2.20 GiB (10.02 GiB after fulltext build) | 10.03 GiB |
| Elasticsearch 9.4.4 | 55.5 s | 53 400 rec/s | 4.47 GiB (4 GiB heap preallocated) | 4.66 GiB | 4.70 GiB |
| Typesense 30.2 | 122.8 s | 24 100 rec/s | 144 MiB | 1.79 GiB | 1.94 GiB |
| Meilisearch v1.51.0 | 48.4 s | 61 100 rec/s | 42 MiB | 1.01 GiB | 1.02 GiB |

Reindexer's HTTP bulk load is the fastest despite emulation (92K rec/s), but
its composite fulltext index is built lazily on the first fulltext query and
costs another ~29 s and, above all, ~7.8 GiB of RAM — with the fulltext index
built, Reindexer is by far the heaviest resident of the four. Elasticsearch's
RSS is dominated by its fixed 4 GiB heap: actual index data adds only
~200 MiB on top (index size on disk: 681 MB). All loads used batched bulk
APIs: Reindexer 5000/batch (HTTP POST /items), Elasticsearch `_bulk`
5000/batch with refresh disabled during the load, Typesense
`/documents/import` 10000/batch (synchronous), Meilisearch NDJSON batches of
100000 (asynchronous task queue; the reported time is until the last task
succeeded, i.e. until the data is searchable).

## Query latency

phpbench through the official PHP clients, 5 iterations per subject, warmup 1.
Mean time per operation; mode in parentheses where it differs notably from
the mean. **Reindexer is the only engine running under CPU emulation.**

| Scenario | revs×its | Reindexer *(emulated)* | Elasticsearch | Typesense | Meilisearch |
|---|---|---|---|---|---|
| Point lookup by id | 200×5 | **131 μs** | 377 μs | 183 μs | 164 μs |
| Filter + sort (`downloads`/`pipeline_tag`, likes desc) | 50×5 | **425 μs** | 1.97 ms | 18.5 ms | 1.72 ms |
| Array contains (`tags` = 'llama') | 50×5 | **395 μs** | 1.70 ms | 11.6 ms | 1.65 ms |
| Date range (90 d) + sort | 50×5 | **337 μs** | 1.78 ms | 42.1 ms | 1.81 ms |
| Fulltext 'bert'/'llama' | 50×5 | 506 μs (mode 394 μs, ±45%) | 2.14 ms | **322 μs** | 10.9 ms |
| Facet `pipeline_tag`, filtered set | 10×5 | **390 μs** | 1.12 ms (mode 936 μs) | 686 μs | 1.28 ms |

rstdev is ≤ ±5% for most Reindexer/Typesense/Meilisearch subjects; noisier
outliers: Reindexer fulltext ±45% (the two alternating terms have different
result-set sizes), Elasticsearch ±9–34% across subjects, Meilisearch fulltext
±22%.

For context, the Reindexer transport suite re-run from the same session (same
numbers as [benchmarks.md](benchmarks.md), confirming the environment did not
drift): point select 156 μs, filter+sort 412 μs, tags 386 μs, date range
337 μs, fulltext 426 μs, whole-namespace facet over all 2.96M rows 209 ms,
HTTP bulk insert of 5000 items 62 ms.

Caveat on caching: every engine answers a repeating query pattern (fixed
filter/fulltext queries, 1000 rotating point-lookup ids), so engine-side
caches (Reindexer query cache, ES node/filter caches, OS page cache for
Meilisearch's LMDB) can serve part of the reps. That is the same for all four
engines and reflects a hot production working set, not cold-start latency.

## Conclusions

- **Reindexer wins every structured-query scenario, under emulation.** Point
  lookup 131 μs vs 164–377 μs, and filter/sort/array/date-range queries are
  4–5× faster than Elasticsearch and Meilisearch and 25–100× faster than
  Typesense on this dataset. With native-arm64 competitors vs an emulated
  Reindexer binary, the real gap on equal hardware is larger than shown.
- **The price is memory.** With the composite fulltext index built, Reindexer
  holds 10.0 GiB RSS — 5× Typesense (1.9 GiB), 10× Meilisearch (1.0 GiB), 2×
  Elasticsearch (4.7 GiB, of which 4 GiB is a fixed heap). Without the
  fulltext index Reindexer sits at 2.2 GiB, competitive with the others; the
  fulltext structure alone costs ~7.8 GiB (~2.6 KiB/record) and is the reason
  a 15.6 GiB VM caps out at ~6.9M records ([benchmarks.md](benchmarks.md#memory-footprint-server-rss-docker-stats)).
- **Fulltext is Typesense's home turf and Meilisearch's weak spot here.**
  Typesense answers 'bert'/'llama' in 322 μs — the only scenario where an
  engine beats Reindexer (506 μs mean under emulation). Meilisearch needs
  ~11 ms per fulltext query (typo tolerance and relevancy ranking are always
  on); Elasticsearch's `multi_match` costs ~2.1 ms.
- **Typesense is not built for filter-heavy browsing at this scale**: wildcard
  query + numeric filter + sort takes 18–42 ms over 2.96M docs, two orders of
  magnitude behind Reindexer, while its point lookups (183 μs) and facets
  (686 μs) are excellent.
- **Everyone ingests 2.96M records comfortably inside 60 minutes** — no
  engine needed the checkpoint rule. Reindexer's HTTP bulk load is the
  fastest raw ingest (32 s, 92K rec/s, plus a lazy 29 s fulltext build on
  first query); Meilisearch surprised with 48 s end-to-end (v1.51's indexer
  is fast — but note its fulltext structures then answer queries 10× slower
  than the others); Elasticsearch took 56 s, Typesense 123 s (its import is
  fully synchronous).
- **Elasticsearch is the balanced middle**: never the fastest, never the
  slowest, in 1–2 ms for everything, with an RSS floor set by the JVM heap
  you give it rather than by the data.

## Reproduce

```bash
# engine up (one at a time!), e.g. Elasticsearch
docker compose -f docker-compose-tests.yml -f benchmarks/docker-compose-engines.yml --profile es up -d elasticsearch

# load the full dataset (see benchmarks.md for seeding models-full.ndjson)
docker compose -f docker-compose-tests.yml -f benchmarks/docker-compose-engines.yml \
    run --rm --no-deps php-grpc php benchmarks/engines/load-elasticsearch.php

# bench + RSS
docker compose -f docker-compose-tests.yml -f benchmarks/docker-compose-engines.yml \
    run --rm --no-deps php-grpc vendor/bin/phpbench run benchmarks/engines/ElasticsearchBench.php --report=transport
docker stats --no-stream reindexer-client-elasticsearch-1

# engine down (volumes too, so the next engine starts clean)
docker compose -f docker-compose-tests.yml -f benchmarks/docker-compose-engines.yml --profile es down -v
```

Same flow with `--profile typesense` / `--profile meili` and the matching
`load-*.php` / `*Bench.php`; for Reindexer use `composer bench-load` and
`benchmarks/engines/ReindexerHttpBench.php` against the `reindexer` service.
