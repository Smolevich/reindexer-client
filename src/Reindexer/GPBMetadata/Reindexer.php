<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace GPBMetadata;

class Reindexer {
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
            return;
        }
        $pool->internalAddGeneratedFile(
            '
�=
reindexer.protoreindexer.grpc"�
ErrorResponse5
code (2\'.reindexer.grpc.ErrorResponse.ErrorCode
what (	"�
	ErrorCode
	errCodeOK 
errCodeParseSQL
errCodeQueryExec
errCodeParams
errCodeLogic
errCodeParseJson
errCodeParseDSL
errCodeConflict
errCodeParseBin
errCodeForbidden	
errCodeWasRelock

errCodeNotValid
errCodeNetwork
errCodeNotFound
errCodeStateInvalidated
errCodeBadTransaction
errCodeOutdatedWAL
errCodeNoWAL
errCodeDataHashMismatch
errCodeTimeout
errCodeCanceled
errCodeTagsMissmatch
errCodeReplParams
errCodeNamespaceInvalidated
errCodeParseMsgPack
errCodeParseProtobuf"\'
CreateDatabaseRequest
dbName (	"7
CloseNamespaceRequest
dbName (	
nsName (	"6
DropNamespaceRequest
dbName (	
nsName (	":
TruncateNamespaceRequest
dbName (	
nsName (	"�
IndexOptions
isPk (
isArray (
isDense (
isSparse (9
	rtreeType (2&.reindexer.grpc.IndexOptions.RTreeType=
collateMode (2(.reindexer.grpc.IndexOptions.CollateMode
sortOrdersTable (	
config (	"=
	RTreeType

Linear 
	Quadratic

Greene	
RStar"|
CollateMode
CollateNoneMode 
CollateASCIIMode
CollateUTF8Mode
CollateNumericMode
CollateCustomMode"�
Index
name (	
	jsonPaths (	
	indexType (	
	fieldType (	-
options (2.reindexer.grpc.IndexOptions
expireAfter ("\\
AddIndexRequest
dbName (	
nsName (	)

definition (2.reindexer.grpc.Index"_
UpdateIndexRequest
dbName (	
nsName (	)

definition (2.reindexer.grpc.Index"]
DropIndexRequest
dbName (	
nsName (	)

definition (2.reindexer.grpc.Index"4
SchemaDefinition
nsName (	
jsonData (	"e
SetSchemaRequest
dbName (	A
schemaDefinitionRequest (2 .reindexer.grpc.SchemaDefinition"�
ModifyItemRequest
dbName (	
nsName (	(
mode (2.reindexer.grpc.ModifyMode2
encodingType (2.reindexer.grpc.EncodingType
data ("I
Query2
encdoingType (2.reindexer.grpc.EncodingType
data ("�
OutputFlags2
encodingType (2.reindexer.grpc.EncodingType

withItemID (
withNsID (
withRank (
withJoinedItems ("[
SelectSqlRequest
dbName (	
sql (	*
flags (2.reindexer.grpc.OutputFlags"q
SelectRequest
dbName (	$
query (2.reindexer.grpc.Query*
flags (2.reindexer.grpc.OutputFlags"q
DeleteRequest
dbName (	$
query (2.reindexer.grpc.Query*
flags (2.reindexer.grpc.OutputFlags"q
UpdateRequest
dbName (	$
query (2.reindexer.grpc.Query*
flags (2.reindexer.grpc.OutputFlags"�
QueryResultsResponse
data (I
options (28.reindexer.grpc.QueryResultsResponse.QueryResultsOptions4
errorResponse (2.reindexer.grpc.ErrorResponsei
QueryResultsOptions

totalItems (
queryTotalItems (
cacheEnabled (
explain (	"�
ConnectOptions
expectedClusterID (
openNamespaces (
allowNamespaceErrors (

autorepair (
disableReplication (0
storageType (2.reindexer.grpc.StorageType"�
ConnectRequest
url (	
dbName (	
login (	
password (	3
connectOpts (2.reindexer.grpc.ConnectOptions"�
StorageOptions
nsName (	
enabled (
dropOnFileFormatError (
createIfMissing (
verifyChecksums (
	fillCache (
sync (
	slaveMode (

autorepair	 ("^
OpenNamespaceRequest
dbName (	6
storageOptions (2.reindexer.grpc.StorageOptions"c
EnumNamespacesOptions
filter (	

withClosed (
	onlyNames (
hideSystems ("_
EnumNamespacesRequest
dbName (	6
options (2%.reindexer.grpc.EnumNamespacesOptions"
EnumDatabasesRequest"�
	Namespace
dbName (	
name (	6
storageOptions (2.reindexer.grpc.StorageOptions1
indexesDefinitions (2.reindexer.grpc.Index
isTemporary ("S
AddNamespaceRequest
dbName (	,
	namespace (2.reindexer.grpc.Namespace"�
EnumNamespacesResponse8
namespacesDefinitions (2.reindexer.grpc.Namespace4
errorResponse (2.reindexer.grpc.ErrorResponse"\\
EnumDatabasesResponse
names (	4
errorResponse (2.reindexer.grpc.ErrorResponse"Z
MetadataResponse
metadata (	4
errorResponse (2.reindexer.grpc.ErrorResponse"6
Metadata
nsName (	
key (	
value (	"L
GetMetaRequest
dbName (	*
metadata (2.reindexer.grpc.Metadata"L
PutMetaRequest
dbName (	*
metadata (2.reindexer.grpc.Metadata"1
EnumMetaRequest
dbName (	
nsName (	"Z
MetadataKeysResponse
keys (	4
errorResponse (2.reindexer.grpc.ErrorResponse"&
CommitTransactionRequest

id ("(
RollbackTransactionRequest

id ("R
TransactionIdResponse-
status (2.reindexer.grpc.ErrorResponse

id ("�
AddTxItemRequest

id ((
mode (2.reindexer.grpc.ModifyMode2
encodingType (2.reindexer.grpc.EncodingType
data ("9
BeginTransactionRequest
dbName (	
nsName (	">
GetProtobufSchemaRequest
dbName (	

namespaces (	"]
ProtobufSchemaResponse
proto (	4
errorResponse (2.reindexer.grpc.ErrorResponse*<

ModifyMode

UPSERT 

INSERT

UPDATE

DELETE*>
EncodingType
JSON 	
CJSON
MSGPACK
PROTOBUF*=
StorageType
StorageTypeLevelDB 
StorageTypeRocksDB2�
	ReindexerJ
Connect.reindexer.grpc.ConnectRequest.reindexer.grpc.ErrorResponse" X
CreateDatabase%.reindexer.grpc.CreateDatabaseRequest.reindexer.grpc.ErrorResponse" V
OpenNamespace$.reindexer.grpc.OpenNamespaceRequest.reindexer.grpc.ErrorResponse" T
AddNamespace#.reindexer.grpc.AddNamespaceRequest.reindexer.grpc.ErrorResponse" X
CloseNamespace%.reindexer.grpc.CloseNamespaceRequest.reindexer.grpc.ErrorResponse" V
DropNamespace$.reindexer.grpc.DropNamespaceRequest.reindexer.grpc.ErrorResponse" ^
TruncateNamespace(.reindexer.grpc.TruncateNamespaceRequest.reindexer.grpc.ErrorResponse" L
AddIndex.reindexer.grpc.AddIndexRequest.reindexer.grpc.ErrorResponse" R
UpdateIndex".reindexer.grpc.UpdateIndexRequest.reindexer.grpc.ErrorResponse" N
	DropIndex .reindexer.grpc.DropIndexRequest.reindexer.grpc.ErrorResponse" N
	SetSchema .reindexer.grpc.SetSchemaRequest.reindexer.grpc.ErrorResponse" a
EnumNamespaces%.reindexer.grpc.EnumNamespacesRequest&.reindexer.grpc.EnumNamespacesResponse" ^
EnumDatabases$.reindexer.grpc.EnumDatabasesRequest%.reindexer.grpc.EnumDatabasesResponse" T

ModifyItem!.reindexer.grpc.ModifyItemRequest.reindexer.grpc.ErrorResponse" (0W
	SelectSql .reindexer.grpc.SelectSqlRequest$.reindexer.grpc.QueryResultsResponse" 0Q
Select.reindexer.grpc.SelectRequest$.reindexer.grpc.QueryResultsResponse" 0Q
Update.reindexer.grpc.UpdateRequest$.reindexer.grpc.QueryResultsResponse" 0Q
Delete.reindexer.grpc.DeleteRequest$.reindexer.grpc.QueryResultsResponse" 0M
GetMeta.reindexer.grpc.GetMetaRequest .reindexer.grpc.MetadataResponse" J
PutMeta.reindexer.grpc.PutMetaRequest.reindexer.grpc.ErrorResponse" S
EnumMeta.reindexer.grpc.EnumMetaRequest$.reindexer.grpc.MetadataKeysResponse" g
GetProtobufSchema(.reindexer.grpc.GetProtobufSchemaRequest&.reindexer.grpc.ProtobufSchemaResponse" d
BeginTransaction\'.reindexer.grpc.BeginTransactionRequest%.reindexer.grpc.TransactionIdResponse" R
	AddTxItem .reindexer.grpc.AddTxItemRequest.reindexer.grpc.ErrorResponse" (0^
CommitTransaction(.reindexer.grpc.CommitTransactionRequest.reindexer.grpc.ErrorResponse" b
RollbackTransaction*.reindexer.grpc.RollbackTransactionRequest.reindexer.grpc.ErrorResponse" bproto3',
            true
        );

        static::$is_initialized = true;
    }
}
