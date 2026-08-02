# API coverage audit: reindexer-client (PHP, v3.0.0) vs Reindexer v5.15.0

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
| `/db/{db}/namespaces/{name}/metabykey/{key}` | DELETE | ❌ | no meta deletion |
| `/db/{db}/namespaces/{name}/metabykey` | PUT | ✅ | `Namespaces::addMetaDataKey` |
| `/db/{db}/namespaces/{name}/items` | GET | ✅ | `Item::get` (limit/offset/sort only; no filter, fields, format, sharding, with_vectors, with_columns) |
| `/db/{db}/namespaces/{name}/items` | PUT | ✅ | `Item::update` (no precepts, format) |
| `/db/{db}/namespaces/{name}/items` | POST | ✅ | `Item::add` (no precepts, format) |
| `/db/{db}/namespaces/{name}/items` | DELETE | ✅ | `Item::delete` (no precepts) |
| `/db/{db}/namespaces/{name}/items` | PATCH (upsert) | ❌ | no upsert over HTTP |
| `/db/{db}/namespaces/{name}/indexes` | GET | ✅ | `Index::get` |
| `/db/{db}/namespaces/{name}/indexes` | POST | ✅ | `Index::create` |
| `/db/{db}/namespaces/{name}/indexes` | PUT (update index) | ❌ | no way to update an index, only drop+create |
| `/db/{db}/namespaces/{name}/indexes/{indexname}` | DELETE | ✅ | `Index::delete` |
| `/db/{db}/namespaces/{name}/schema` | GET | ❌ | no JSON-schema read |
| `/db/{db}/namespaces/{name}/schema` | PUT | ✅ | `Namespaces::schema` |
| `/db/{db}/protobuf_schema` | GET | ❌ | protobuf schemas are not exported |
| `/query/convert/sql` | POST (SQL→DSL) | ❌ | |
| `/query/convert/dsl` | POST (DSL→SQL) | ❌ | |
| `/db/{db}/query` | GET (SQL select) | ✅ | `Query::createByHttpGet` (no with_columns/width/format) |
| `/db/{db}/query` | PUT (DSL update) | ❌ | no update query via DSL |
| `/db/{db}/query` | POST (DSL select) | ✅ | `Query::createSdlQueryByHttpPost` |
| `/db/{db}/query` | DELETE (SQL delete) | ❌ | |
| `/db/{db}/namespaces/{name}/transactions/begin` | POST | ❌ | no HTTP transactions at all |
| `/db/{db}/transactions/{tx_id}/commit` | POST | ❌ | |
| `/db/{db}/transactions/{tx_id}/rollback` | POST | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | PUT | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | POST | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | DELETE | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | PATCH | ❌ | |
| `/db/{db}/transactions/{tx_id}/query` | GET (SQL in tx) | ❌ | |
| `/db/{db}/transactions/{tx_id}/query` | DELETE | ❌ | |
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

REST total: **23/55 directly = 42%**; counting indirectly reachable system namespaces — 31/55 = 56%. 24 endpoints are entirely missing.

## 2. gRPC coverage (`GrpcClient.php` vs `proto/reindexer.proto`, 27 RPCs)

| RPC | Status | Wrapper method |
|---|---|---|
| Connect | ✅ | `connect` |
| CreateDatabase | ✅ | `createDatabase` |
| OpenNamespace | ✅ | `openNamespace` |
| AddNamespace | ❌ | (Open only; Add with a full NamespaceDefinition is not exposed) |
| CloseNamespace | ✅ | `closeNamespace` |
| DropNamespace | ✅ | `dropNamespace` |
| TruncateNamespace | ✅ | `truncateNamespace` |
| AddIndex | ✅ | `addIndex` |
| UpdateIndex | ✅ | `updateIndex` |
| DropIndex | ✅ | `dropIndex` |
| SetSchema | ❌ | |
| EnumNamespaces | ✅ | `enumNamespaces` (onlyNames+hideSystems are hardcoded → full NamespaceDefinition is unavailable) |
| EnumDatabases | ✅ | `enumDatabases` |
| ModifyItem (bidi stream) | ✅ | `modifyItems` |
| ExecSql (stream) | ✅ | `execSql` |
| Select (stream) | ✅ | `select` |
| Update (stream) | ✅ | `update` |
| Delete (stream) | ✅ | `delete` |
| GetMeta | ❌ | |
| PutMeta | ❌ | |
| EnumMeta | ❌ | |
| DeleteMeta | ❌ | |
| GetProtobufSchema | ❌ | |
| BeginTransaction | ✅ | `beginTransaction` |
| AddTxItem (bidi stream) | ✅ | `addTxItems` |
| CommitTransaction | ✅ | `commitTransaction` |
| RollbackTransaction | ✅ | `rollbackTransaction` |

gRPC total: **20/27 = 74%**. 7 RPCs are not exposed: AddNamespace, SetSchema, GetMeta, PutMeta, EnumMeta, DeleteMeta, GetProtobufSchema.

Limitations within the exposed RPCs:
- `buildIndex()` ignores the proto fields `IndexOptions.collateMode`, `rtreeType`, `sortOrdersTable`, `config` (fulltext/float-vector config) — even though the proto supports them.
- Encoding is hardwired to JSON (`EncodingType::JSON`); CJSON/MSGPACK/PROTOBUF and the `OutputFlags` `withRank`/`withJoinedItems`/`withItemID` are unavailable.

## 3. Capabilities within endpoints

| Capability | Spec v5.15 | Client | Verdict |
|---|---|---|---|
| precepts (`serial()`, `now()`) | query param on items POST/PUT/DELETE/PATCH and tx items | absent from `Item::*`; the proto `ModifyItemRequest` has no precepts field at all | **not supported in either transport** |
| IndexType | `hash, tree, text, rtree, ttl, '-'` | enum: `hash, tree, text, '-'` | **no `rtree`, `ttl`** |
| FieldType | `int, int64, double, string, bool, composite, point` | no `point` | **no `point`** (geo) |
| Index entity fields | + `is_sparse`, `is_no_column`, `expire_after`, `rtree_type`, `is_simple_tag`, `config` (FulltextConfig / FloatVectorConfig) | only name/json_paths/types/is_pk/is_array/is_dense/is_appendable/collate/sort_order | **TTL, rtree, sparse, fulltext config and vectors are unreachable through the typed API** (and `Index::create` accepts only `IndexEntity` → a raw array workaround is not possible over HTTP) |
| Fulltext config (FulltextConfig) | `config` in IndexDef | neither in the HTTP entity nor in gRPC `buildIndex` | not supported |
| KNN / float_vector (hnsw/ivf, KNN filters, `with_vectors`) | present in the master spec | not reflected anywhere | not supported |
| Aggregations, joined queries, explain | in Query DSL (`aggregations`, `filters[].join_query`, `explain`) | pass-through: `Query::createSdlQueryByHttpPost` and `GrpcClient::select` accept a raw array/JSON → works | ✅ works, but untyped |
| `SdlQuery` entity | current DSL: `merge_queries`, join inside `filters`, `explain`, `req_total: disabled/enabled/cached`, `update_fields`, `drop_fields`, `type` | keys `joined`/`merged` (legacy DSL), `reqTotal` bool, no explain/update_fields/drop_fields | **drift from the spec**: the typed builder partially generates invalid/legacy DSL |
| Protobuf schemas | `GET /db/{db}/protobuf_schema`, RPC GetProtobufSchema, `schema` GET | only `schema` PUT | not supported |
| Response formats (json/msgpack/protobuf/csv-file), `with_columns`, `width`, `sharding` | query params on items/query | not exposed | not supported |

## 4. Prioritized gaps

### (a) Important for real users
1. **HTTP transactions** (9 endpoints) — atomic bulk writes; available over gRPC, but HTTP-only users (most PHP hostings lack ext-grpc) are left without them entirely.
2. **precepts** — `serial()`/`now()` is the standard way to get auto-increment and timestamps in Reindexer; currently unreachable altogether.
3. **`PUT /indexes` (update index) + the `ttl`/`rtree` types + `is_sparse`/`expire_after`/fulltext `config`** — without these one can neither update an index without losing it nor create a TTL/geo/tuned fulltext index.
4. **`PATCH /items` (upsert)** — the most used write mode in Reindexer clients for other languages.
5. **`PUT /query` and `DELETE /query`** — conditional update/delete without a select; currently only via raw SQL `sqlquery`.
6. **Bringing `SdlQuery` up to date** — the legacy keys `joined`/`merged` instead of `merge_queries`/`filters[].join_query` produce silently ignored DSL.
7. **gRPC meta (GetMeta/PutMeta/EnumMeta/DeleteMeta) + HTTP `DELETE metabykey`** — an asymmetry: meta can be written/read over HTTP but not deleted; over gRPC — nothing at all.

### (b) Niche
1. `GET /check`, `GET /db/{db}/namespaces/#memstats` etc. — monitoring; partially reachable via `Item::get('#memstats')`, but convenient typed helpers would be useful for ops.
2. `GET /db/{db}/suggest`, `/query/convert/*` — needed by admin panels/IDE-like tools, not applications.
3. `GET schema` / `protobuf_schema` / gRPC SetSchema+GetProtobufSchema — needed only by those running the protobuf encoding.
4. KNN / float_vector indexes — new v5 functionality; demand for vector search from PHP is still sporadic (and it partially works via raw DSL select).
5. The msgpack/protobuf/csv formats and `with_columns`/`width` — traffic optimization and console output.
6. gRPC `AddNamespace` (with a full NamespaceDefinition) and non-JSON EncodingType in gRPC.

### (c) Deliberately skippable
1. `/allocator/drop_cache`, `/allocator/info` — low-level tcmalloc tuning; a job for ops tools, not a client library.
2. `/user/role` — introspection of the current user's permissions, needed only by the admin UI (face).
3. `/db/default_configs` — default configs for the UI configuration editor.
4. System `#...stats` as dedicated methods — already reachable via the generic `Item::get`; dedicated wrappers would duplicate the URL.
5. `GET /db/{db}/transactions/{tx_id}/query` (SQL inside a tx over HTTP) — if HTTP transactions get implemented, items+commit/rollback is enough: SQL-in-tx is rarely used even in the official clients.

## 5. Summary

- **REST: 42%** (23/55 directly; 56% counting indirectly reachable system namespaces).
- **gRPC: 74%** (20/27 RPCs).
