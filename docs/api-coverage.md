# API coverage audit: reindexer-client (PHP, v3.1.0) vs Reindexer v5.15.0

Sources:
- REST: `cpp_src/server/contrib/server.yml` (Restream/reindexer, master, 7066 lines) — 55 endpoint+method pairs.
- gRPC: `proto/reindexer.proto` in the client repo — 27 RPCs of the `Reindexer` service.
- Client: `src/Reindexer/Services/{Database,Namespaces,Index,Item,Query}.php`, `src/Reindexer/Client/Api.php`, `src/Reindexer/Transport/Grpc/GrpcClient.php`.

`Client/Api.php` is a pure Guzzle transport (`request($method,$uri,...)`) with no knowledge of endpoints; all coverage is determined by the Services.

## 1. REST coverage

Legend: ✅ covered directly; 🟡 reachable indirectly (same URL via a generic method, no dedicated SDK method); ❌ not covered.

| Endpoint | Method | Status | SDK method |
|---|---|---|---|
| `/db` | GET | ✅ | `Database::getList` |
| `/db` | POST | ✅ | `Database::create` |
| `/db/{database}` | DELETE | ✅ | `Database::drop` |
| `/db/{db}/namespaces` | GET | ✅ | `Namespaces::getList` |
| `/db/{db}/namespaces` | POST | ✅ | `Namespaces::create` |
| `/db/{db}/namespaces/{name}` | GET | ✅ | `Namespaces::get` |
| `/db/{db}/namespaces/{name}` | DELETE | ✅ | `Namespaces::drop` |
| `/db/{db}/namespaces/{name}/truncate` | DELETE | ✅ | `Namespaces::truncate` |
| `/db/{db}/namespaces/{name}/rename/{newname}` | GET | ✅ | `Namespaces::rename` |
| `/db/{db}/namespaces/{name}/metalist` | GET | ✅ | `Namespaces::getMetaList` |
| `/db/{db}/namespaces/{name}/metabykey/{key}` | GET | ✅ | `Namespaces::getMetaDataKey` |
| `/db/{db}/namespaces/{name}/metabykey/{key}` | DELETE | ✅ | `Namespaces::deleteMetaDataKey` (implemented in v3.1.0) |
| `/db/{db}/namespaces/{name}/metabykey` | PUT | ✅ | `Namespaces::addMetaDataKey` |
| `/db/{db}/namespaces/{name}/items` | GET | ✅ | `Item::get` (limit/offset/sort only; no filter, fields, format, sharding, with_vectors, with_columns) |
| `/db/{db}/namespaces/{name}/items` | PUT | ✅ | `Item::update` (precepts since v3.1.0; no format) |
| `/db/{db}/namespaces/{name}/items` | POST | ✅ | `Item::add` (precepts since v3.1.0; no format) |
| `/db/{db}/namespaces/{name}/items` | DELETE | ✅ | `Item::delete` (precepts since v3.1.0) |
| `/db/{db}/namespaces/{name}/items` | PATCH (upsert) | ✅ | `Item::upsert` (implemented in v3.1.0) |
| `/db/{db}/namespaces/{name}/indexes` | GET | ✅ | `Index::get` |
| `/db/{db}/namespaces/{name}/indexes` | POST | ✅ | `Index::create` |
| `/db/{db}/namespaces/{name}/indexes` | PUT (update index) | ✅ | `Index::update` (implemented in v3.1.0) |
| `/db/{db}/namespaces/{name}/indexes/{indexname}` | DELETE | ✅ | `Index::delete` |
| `/db/{db}/namespaces/{name}/schema` | GET | ❌ | no JSON-schema read |
| `/db/{db}/namespaces/{name}/schema` | PUT | ✅ | `Namespaces::schema` |
| `/db/{db}/protobuf_schema` | GET | ❌ | protobuf schemas are not exported |
| `/query/convert/sql` | POST (SQL→DSL) | ❌ | |
| `/query/convert/dsl` | POST (DSL→SQL) | ❌ | |
| `/db/{db}/query` | GET (SQL select) | ✅ | `Query::createByHttpGet` (no with_columns/width/format) |
| `/db/{db}/query` | PUT (DSL update) | ✅ | `Query::updateSdlQueryByHttpPut` (implemented in v3.1.0) |
| `/db/{db}/query` | POST (DSL select) | ✅ | `Query::createSdlQueryByHttpPost` |
| `/db/{db}/query` | DELETE (DSL delete) | ✅ | `Query::deleteSdlQueryByHttpDelete` (implemented in v3.1.0) |
| `/db/{db}/namespaces/{name}/transactions/begin` | POST | ✅ | `Transaction::begin` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/commit` | POST | ✅ | `Transaction::commit` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/rollback` | POST | ✅ | `Transaction::rollback` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/items` | PUT | ✅ | `Transaction::updateItem` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/items` | POST | ✅ | `Transaction::addItem` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/items` | DELETE | ✅ | `Transaction::deleteItem` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/items` | PATCH | ✅ | `Transaction::upsertItem` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/query` | GET (SQL in tx) | ✅ | `Transaction::sqlQuery` (implemented in v3.1.0) |
| `/db/{db}/transactions/{tx_id}/query` | DELETE | ✅ | `Transaction::deleteQuery` (implemented in v3.1.0) |
| `/db/{db}/suggest` | GET (SQL autocompletion) | ❌ | |
| `/db/{db}/sqlquery` | POST (SQL) | ✅ | `Query::createSqlQueryByHttpPost` |
| `/check` | GET (health/server version) | ❌ | |
| `/allocator/drop_cache` | POST | ❌ | |
| `/allocator/info` | GET | ❌ | |
| `/user/role` | GET | ❌ | |
| `/db/{db}/namespaces/#activitystats/items` | GET | 🟡 | `Item::get` with namespace `#activitystats` |
| `/db/{db}/namespaces/#clientsstats/items` | GET | 🟡 | same |
| `/db/{db}/namespaces/#replicationstats/items` | GET | 🟡 | same |
| `/db/{db}/namespaces/#memstats/items` | GET | 🟡 | same |
| `/db/{db}/namespaces/#perfstats/items` | GET | 🟡 | same |
| `/db/{db}/namespaces/#queriesperfstats/items` | GET | 🟡 | same |
| `/db/{db}/namespaces/#config/items` | GET | 🟡 | same |
| `/db/{db}/namespaces/#config/items` | PUT | 🟡 | `Item::update` with namespace `#config` |
| `/db/default_configs` | GET | ❌ | |

REST total: **37/55 directly = 67%**; counting indirectly reachable system namespaces — 45/55 = 82%. 10 endpoints are entirely missing.

## 2. gRPC coverage (`GrpcClient.php` vs `proto/reindexer.proto`, 27 RPCs)

| RPC | Status | Wrapper method |
|---|---|---|
| Connect | ✅ | `connect` |
| CreateDatabase | ✅ | `createDatabase` |
| OpenNamespace | ✅ | `openNamespace` |
| AddNamespace | ✅ | `addNamespace` (implemented in v3.1.0) |
| CloseNamespace | ✅ | `closeNamespace` |
| DropNamespace | ✅ | `dropNamespace` |
| TruncateNamespace | ✅ | `truncateNamespace` |
| AddIndex | ✅ | `addIndex` |
| UpdateIndex | ✅ | `updateIndex` |
| DropIndex | ✅ | `dropIndex` |
| SetSchema | ✅ | `setSchema` (implemented in v3.1.0) |
| EnumNamespaces | ✅ | `enumNamespaces` (onlyNames+hideSystems are hardcoded → full NamespaceDefinition is unavailable) |
| EnumDatabases | ✅ | `enumDatabases` |
| ModifyItem (bidi stream) | ✅ | `modifyItems` |
| ExecSql (stream) | ✅ | `execSql` |
| Select (stream) | ✅ | `select` |
| Update (stream) | ✅ | `update` |
| Delete (stream) | ✅ | `delete` |
| GetMeta | ✅ | `getMeta` (implemented in v3.1.0) |
| PutMeta | ✅ | `putMeta` (implemented in v3.1.0) |
| EnumMeta | ✅ | `enumMeta` (implemented in v3.1.0) |
| DeleteMeta | ✅ | `deleteMeta` (implemented in v3.1.0) |
| GetProtobufSchema | ✅ | `getProtobufSchema` (implemented in v3.1.0) |
| BeginTransaction | ✅ | `beginTransaction` |
| AddTxItem (bidi stream) | ✅ | `addTxItems` |
| CommitTransaction | ✅ | `commitTransaction` |
| RollbackTransaction | ✅ | `rollbackTransaction` |

gRPC total: **27/27 = 100%** (since v3.1.0).

Limitations within the exposed RPCs:
- `buildIndex()` ignores the proto fields `IndexOptions.collateMode`, `rtreeType`, `sortOrdersTable`, `config` (fulltext/float-vector config) — even though the proto supports them.
- Encoding is hardwired to JSON (`EncodingType::JSON`); CJSON/MSGPACK/PROTOBUF and the `OutputFlags` `withRank`/`withJoinedItems`/`withItemID` are unavailable.

## 3. Capabilities within endpoints

| Capability | Spec v5.15 | Client | Verdict |
|---|---|---|---|
| precepts (`serial()`, `now()`) | query param on items POST/PUT/DELETE/PATCH and tx items | ✅ HTTP since v3.1.0 (`Item::*` and `Transaction::*Item`); the proto `ModifyItemRequest` has no precepts field at all | **supported over HTTP; unavailable over gRPC by proto design** |
| IndexType | `hash, tree, text, rtree, ttl, '-'` | full set since v3.1.0 | ✅ |
| FieldType | `int, int64, double, string, bool, composite, point` | full set since v3.1.0 | ✅ |
| Index entity fields | + `is_sparse`, `is_no_column`, `expire_after`, `rtree_type`, `is_simple_tag`, `config` (FulltextConfig / FloatVectorConfig) | v3.1.0 adds `is_sparse`, `expire_after`, `rtree_type` (enum `RtreeType`), `config` (raw array) | **mostly supported**; `is_no_column` and `is_simple_tag` are still absent |
| Fulltext config (FulltextConfig) | `config` in IndexDef | HTTP: `Index::setConfig(array)` since v3.1.0; gRPC `buildIndex` still ignores `config` | supported over HTTP (untyped array) |
| KNN / float_vector (hnsw/ivf, KNN filters, `with_vectors`) | present in the master spec | not reflected anywhere | not supported |
| Aggregations, joined queries, explain | in Query DSL (`aggregations`, `filters[].join_query`, `explain`) | pass-through: `Query::createSdlQueryByHttpPost` and `GrpcClient::select` accept a raw array/JSON → works | ✅ works, but untyped |
| `SdlQuery` entity | current DSL: `merge_queries`, join inside `filters`, `explain`, `req_total: disabled/enabled/cached`, `update_fields`, `drop_fields`, `type` | v3.1.0: all listed keys + fluent setters; `req_total` emits the server enum (booleans normalized); legacy `joined`/`merged` kept but deprecated | ✅ up to date |
| Protobuf schemas | `GET /db/{db}/protobuf_schema`, RPC GetProtobufSchema, `schema` GET | only `schema` PUT | not supported |
| Response formats (json/msgpack/protobuf/csv-file), `with_columns`, `width`, `sharding` | query params on items/query | not exposed | not supported |

## 4. Prioritized gaps

### (a) Important for real users — all closed in v3.1.0
1. ~~HTTP transactions (9 endpoints)~~ — `Services\Transaction` (begin, item ops with precepts, SQL/DSL queries in tx, commit/rollback).
2. ~~precepts~~ — `serial()`/`now()` on every `Item` write method and on transaction item ops.
3. ~~`PUT /indexes` + `ttl`/`rtree` types + `is_sparse`/`expire_after`/fulltext `config`~~ — `Index::update`, full `IndexType`/`FieldType` enums, new `RtreeType` enum, entity fields.
4. ~~`PATCH /items` (upsert)~~ — `Item::upsert`.
5. ~~`PUT /query` and `DELETE /query`~~ — `Query::updateSdlQueryByHttpPut` / `Query::deleteSdlQueryByHttpDelete`.
6. ~~Bringing `SdlQuery` up to date~~ — current DSL keys + fluent setters; legacy `joined`/`merged` deprecated but kept.
7. ~~gRPC meta + HTTP `DELETE metabykey`~~ — `GrpcClient::{get,put,enum,delete}Meta`, `Namespaces::deleteMetaDataKey`.

### (b) Niche (still open)
1. `GET /check`, `GET /db/{db}/namespaces/#memstats` etc. — monitoring; partially reachable via `Item::get('#memstats')`, but convenient typed helpers would be useful for ops.
2. `GET /db/{db}/suggest`, `/query/convert/*` — needed by admin panels/IDE-like tools, not applications.
3. `GET schema` / `GET protobuf_schema` over HTTP — the gRPC counterparts (SetSchema, GetProtobufSchema) are exposed since v3.1.0; the HTTP reads are not.
4. KNN / float_vector indexes — new v5 functionality; demand for vector search from PHP is still sporadic (and it partially works via raw DSL select; the index `config` can now at least be passed as an array).
5. The msgpack/protobuf/csv formats and `with_columns`/`width` — traffic optimization and console output.
6. Non-JSON EncodingType and `OutputFlags` variants in gRPC; `config`/`collateMode`/`sortOrdersTable` in gRPC `buildIndex`.
7. `Index` entity: `is_no_column`, `is_simple_tag`.

### (c) Deliberately skippable
1. `/allocator/drop_cache`, `/allocator/info` — low-level tcmalloc tuning; a job for ops tools, not a client library.
2. `/user/role` — introspection of the current user's permissions, needed only by the admin UI (face).
3. `/db/default_configs` — default configs for the UI configuration editor.
4. System `#...stats` as dedicated methods — already reachable via the generic `Item::get`; dedicated wrappers would duplicate the URL.

## 5. Summary

- **REST: 67%** (37/55 directly; 82% counting indirectly reachable system namespaces) — up from 42%/56% in v3.0.0.
- **gRPC: 100%** (27/27 RPCs) — up from 74% in v3.0.0.
