# Аудит покрытия API: reindexer-client (PHP, v3.0.0) vs Reindexer v5.15.0

Источники:
- REST: `cpp_src/server/contrib/server.yml` (Restream/reindexer, master, 7066 строк) — 55 пар endpoint+method.
- gRPC: `proto/reindexer.proto` в репо клиента — 27 RPC сервиса `Reindexer`.
- Клиент: `src/Reindexer/Services/{Database,Namespaces,Index,Item,Query}.php`, `src/Reindexer/Client/Api.php`, `src/Reindexer/Transport/Grpc/GrpcClient.php`.

`Client/Api.php` — чистый Guzzle-транспорт (`request($method,$uri,...)`), сам endpoints не знает; всё покрытие определяется Services.

## 1. REST-покрытие

Легенда: ✅ покрыт напрямую; 🟡 достижим косвенно (тем же URL через generic-метод, отдельного метода SDK нет); ❌ не покрыт.

| Endpoint | Method | Статус | Метод SDK |
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
| `/db/{db}/namespaces/{name}/metabykey/{key}` | DELETE | ❌ | удаление меты нет |
| `/db/{db}/namespaces/{name}/metabykey` | PUT | ✅ | `Namespaces::addMetaDataKey` |
| `/db/{db}/namespaces/{name}/items` | GET | ✅ | `Item::get` (только limit/offset/sort; без filter, fields, format, sharding, with_vectors, with_columns) |
| `/db/{db}/namespaces/{name}/items` | PUT | ✅ | `Item::update` (без precepts, format) |
| `/db/{db}/namespaces/{name}/items` | POST | ✅ | `Item::add` (без precepts, format) |
| `/db/{db}/namespaces/{name}/items` | DELETE | ✅ | `Item::delete` (без precepts) |
| `/db/{db}/namespaces/{name}/items` | PATCH (upsert) | ❌ | upsert по HTTP нет |
| `/db/{db}/namespaces/{name}/indexes` | GET | ✅ | `Index::get` |
| `/db/{db}/namespaces/{name}/indexes` | POST | ✅ | `Index::create` |
| `/db/{db}/namespaces/{name}/indexes` | PUT (update index) | ❌ | изменить индекс нельзя, только drop+create |
| `/db/{db}/namespaces/{name}/indexes/{indexname}` | DELETE | ✅ | `Index::delete` |
| `/db/{db}/namespaces/{name}/schema` | GET | ❌ | чтения JSON-schema нет |
| `/db/{db}/namespaces/{name}/schema` | PUT | ✅ | `Namespaces::schema` |
| `/db/{db}/protobuf_schema` | GET | ❌ | protobuf-схемы не выгружаются |
| `/query/convert/sql` | POST (SQL→DSL) | ❌ | |
| `/query/convert/dsl` | POST (DSL→SQL) | ❌ | |
| `/db/{db}/query` | GET (SQL select) | ✅ | `Query::createByHttpGet` (без with_columns/width/format) |
| `/db/{db}/query` | PUT (DSL update) | ❌ | update-запрос через DSL нет |
| `/db/{db}/query` | POST (DSL select) | ✅ | `Query::createSdlQueryByHttpPost` |
| `/db/{db}/query` | DELETE (SQL delete) | ❌ | |
| `/db/{db}/namespaces/{name}/transactions/begin` | POST | ❌ | HTTP-транзакций нет вообще |
| `/db/{db}/transactions/{tx_id}/commit` | POST | ❌ | |
| `/db/{db}/transactions/{tx_id}/rollback` | POST | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | PUT | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | POST | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | DELETE | ❌ | |
| `/db/{db}/transactions/{tx_id}/items` | PATCH | ❌ | |
| `/db/{db}/transactions/{tx_id}/query` | GET (SQL в tx) | ❌ | |
| `/db/{db}/transactions/{tx_id}/query` | DELETE | ❌ | |
| `/db/{db}/suggest` | GET (SQL-автодополнение) | ❌ | |
| `/db/{db}/sqlquery` | POST (SQL) | ✅ | `Query::createSqlQueryByHttpPost` |
| `/check` | GET (health/версия сервера) | ❌ | |
| `/allocator/drop_cache` | POST | ❌ | |
| `/allocator/info` | GET | ❌ | |
| `/user/role` | GET | ❌ | |
| `/db/{db}/namespaces/#activitystats/items` | GET | 🟡 | `Item::get` с namespace `#activitystats` |
| `/db/{db}/namespaces/#clientsstats/items` | GET | 🟡 | то же |
| `/db/{db}/namespaces/#replicationstats/items` | GET | 🟡 | то же |
| `/db/{db}/namespaces/#memstats/items` | GET | 🟡 | то же |
| `/db/{db}/namespaces/#perfstats/items` | GET | 🟡 | то же |
| `/db/{db}/namespaces/#queriesperfstats/items` | GET | 🟡 | то же |
| `/db/{db}/namespaces/#config/items` | GET | 🟡 | то же |
| `/db/{db}/namespaces/#config/items` | PUT | 🟡 | `Item::update` с namespace `#config` |
| `/db/default_configs` | GET | ❌ | |

Итог REST: **23/55 напрямую = 42%**; с учётом косвенно достижимых системных namespace — 31/55 = 56%. Полностью отсутствуют 24 endpoint.

## 2. gRPC-покрытие (`GrpcClient.php` vs `proto/reindexer.proto`, 27 RPC)

| RPC | Статус | Метод обёртки |
|---|---|---|
| Connect | ✅ | `connect` |
| CreateDatabase | ✅ | `createDatabase` |
| OpenNamespace | ✅ | `openNamespace` |
| AddNamespace | ❌ | (только Open; Add с полным NamespaceDefinition не экспонирован) |
| CloseNamespace | ✅ | `closeNamespace` |
| DropNamespace | ✅ | `dropNamespace` |
| TruncateNamespace | ✅ | `truncateNamespace` |
| AddIndex | ✅ | `addIndex` |
| UpdateIndex | ✅ | `updateIndex` |
| DropIndex | ✅ | `dropIndex` |
| SetSchema | ❌ | |
| EnumNamespaces | ✅ | `enumNamespaces` (onlyNames+hideSystems захардкожены → полные NamespaceDefinition недоступны) |
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

Итог gRPC: **20/27 = 74%**. Не экспонированы 7 RPC: AddNamespace, SetSchema, GetMeta, PutMeta, EnumMeta, DeleteMeta, GetProtobufSchema.

Ограничения внутри экспонированных RPC:
- `buildIndex()` игнорирует поля proto `IndexOptions.collateMode`, `rtreeType`, `sortOrdersTable`, `config` (fulltext/float-vector конфиг) — хотя proto их поддерживает.
- Кодировка жёстко JSON (`EncodingType::JSON`); CJSON/MSGPACK/PROTOBUF и `OutputFlags` `withRank`/`withJoinedItems`/`withItemID` недоступны.

## 3. Возможности внутри endpoints

| Возможность | Спека v5.15 | Клиент | Вердикт |
|---|---|---|---|
| precepts (`serial()`, `now()`) | query-param у items POST/PUT/DELETE/PATCH и tx items | нет в `Item::*`; в proto `ModifyItemRequest` поля precepts нет вовсе | **не поддержано ни в одном транспорте** |
| IndexType | `hash, tree, text, rtree, ttl, '-'` | enum: `hash, tree, text, '-'` | **нет `rtree`, `ttl`** |
| FieldType | `int, int64, double, string, bool, composite, point` | нет `point` | **нет `point`** (geo) |
| Index entity поля | + `is_sparse`, `is_no_column`, `expire_after`, `rtree_type`, `is_simple_tag`, `config` (FulltextConfig / FloatVectorConfig) | только name/json_paths/типы/is_pk/is_array/is_dense/is_appendable/collate/sort_order | **TTL, rtree, sparse, fulltext-конфиг, вектора недоступны через типизированный API** (а `Index::create` принимает только `IndexEntity` → и raw-массивом не обойти по HTTP) |
| Fulltext-конфиг (FulltextConfig) | `config` в IndexDef | ни в HTTP-entity, ни в gRPC `buildIndex` | не поддержано |
| KNN / float_vector (hnsw/ivf, KNN-фильтры, `with_vectors`) | есть в спеке master | нигде не отражено | не поддержано |
| Агрегации, joined queries, explain | в Query DSL (`aggregations`, `filters[].join_query`, `explain`) | pass-through: `Query::createSdlQueryByHttpPost` и `GrpcClient::select` принимают сырой массив/JSON → работает | ✅ работает, но без типизации |
| `SdlQuery` entity | актуальный DSL: `merge_queries`, join внутри `filters`, `explain`, `req_total: disabled/enabled/cached`, `update_fields`, `drop_fields`, `type` | ключи `joined`/`merged` (устаревший DSL), `reqTotal` bool, нет explain/update_fields/drop_fields | **дрейф от спеки**: типизированный билдер частично генерирует невалидный/устаревший DSL |
| Protobuf schemas | `GET /db/{db}/protobuf_schema`, RPC GetProtobufSchema, `schema` GET | только `schema` PUT | не поддержано |
| Форматы ответа (json/msgpack/protobuf/csv-file), `with_columns`, `width`, `sharding` | query-params items/query | не экспонированы | не поддержано |

## 4. Приоритизированные пробелы

### (a) Важные для реальных пользователей
1. **HTTP-транзакции** (9 endpoints) — атомарные bulk-записи; в gRPC есть, но HTTP-only пользователи (большинство PHP-хостингов без ext-grpc) лишены их полностью.
2. **precepts** — `serial()`/`now()` это стандартный способ автоинкремента и timestamps в Reindexer; сейчас недостижимо в принципе.
3. **`PUT /indexes` (update index) + типы `ttl`/`rtree` + `is_sparse`/`expire_after`/fulltext `config`** — без этого нельзя ни изменить индекс без потери, ни создать TTL/geo/настроенный fulltext-индекс.
4. **`PATCH /items` (upsert)** — самый используемый режим записи в Reindexer-клиентах других языков.
5. **`PUT /query` и `DELETE /query`** — update/delete по условию без выборки; сейчас только через сырой SQL `sqlquery`.
6. **Актуализация `SdlQuery`** — устаревшие ключи `joined`/`merged` вместо `merge_queries`/`filters[].join_query` дают тихо игнорируемый DSL.
7. **gRPC meta (GetMeta/PutMeta/EnumMeta/DeleteMeta) + HTTP `DELETE metabykey`** — асимметрия: мету можно писать/читать по HTTP, но не удалять; по gRPC — вообще ничего.

### (b) Нишевые
1. `GET /check`, `GET /db/{db}/namespaces/#memstats` и пр. — мониторинг; частично достижимо через `Item::get('#memstats')`, но удобные типизированные хелперы были бы полезны для ops.
2. `GET /db/{db}/suggest`, `/query/convert/*` — нужны админкам/IDE-подобным инструментам, не приложениям.
3. `GET schema` / `protobuf_schema` / gRPC SetSchema+GetProtobufSchema — нужны только тем, кто гоняет protobuf-кодировку.
4. KNN / float_vector индексы — новая функциональность v5; для vector search из PHP спрос пока точечный (и через raw DSL select он частично проходит).
5. Форматы msgpack/protobuf/csv и `with_columns`/`width` — оптимизация трафика и консольный вывод.
6. gRPC `AddNamespace` (с полным NamespaceDefinition) и не-JSON EncodingType в gRPC.

### (c) Сознательно можно не делать
1. `/allocator/drop_cache`, `/allocator/info` — низкоуровневый tcmalloc-тюнинг, это задача ops-инструментов, не клиентской библиотеки.
2. `/user/role` — интроспекция прав текущего пользователя, нужна только UI-админке (face).
3. `/db/default_configs` — дефолтные конфиги для UI-редактора конфигурации.
4. Системные `#...stats` как отдельные методы — уже достижимы generic `Item::get`, отдельные обёртки дублируют URL.
5. `GET /db/{db}/transactions/{tx_id}/query` (SQL внутри tx по HTTP) — если делать HTTP-транзакции, можно ограничиться items+commit/rollback: SQL-в-tx редко используется даже в официальных клиентах.

## 5. Итог

- **REST: 42%** (23/55 напрямую; 56% с учётом косвенно достижимых системных namespace).
- **gRPC: 74%** (20/27 RPC).
