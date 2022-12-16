<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Reindexer\Grpc;

/**
 * Reindexer service definition.
 */
class ReindexerClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Reindexer\Grpc\ConnectRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Connect(\Reindexer\Grpc\ConnectRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/Connect',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\CreateDatabaseRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateDatabase(\Reindexer\Grpc\CreateDatabaseRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/CreateDatabase',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\OpenNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function OpenNamespace(\Reindexer\Grpc\OpenNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/OpenNamespace',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\AddNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AddNamespace(\Reindexer\Grpc\AddNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/AddNamespace',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\CloseNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CloseNamespace(\Reindexer\Grpc\CloseNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/CloseNamespace',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\DropNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DropNamespace(\Reindexer\Grpc\DropNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/DropNamespace',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\TruncateNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TruncateNamespace(\Reindexer\Grpc\TruncateNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/TruncateNamespace',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\AddIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AddIndex(\Reindexer\Grpc\AddIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/AddIndex',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\UpdateIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateIndex(\Reindexer\Grpc\UpdateIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/UpdateIndex',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\DropIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DropIndex(\Reindexer\Grpc\DropIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/DropIndex',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\SetSchemaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetSchema(\Reindexer\Grpc\SetSchemaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/SetSchema',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\EnumNamespacesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function EnumNamespaces(\Reindexer\Grpc\EnumNamespacesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/EnumNamespaces',
        $argument,
        ['\Reindexer\Grpc\EnumNamespacesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\EnumDatabasesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function EnumDatabases(\Reindexer\Grpc\EnumDatabasesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/EnumDatabases',
        $argument,
        ['\Reindexer\Grpc\EnumDatabasesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\BidiStreamingCall
     */
    public function ModifyItem(\Reindexer\Grpc\ModifyItemRequest $argument,
       $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/ModifyItem',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse','decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\SelectSqlRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function SelectSql(\Reindexer\Grpc\SelectSqlRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/reindexer.grpc.Reindexer/SelectSql',
        $argument,
        ['\Reindexer\Grpc\QueryResultsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\SelectRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Select(\Reindexer\Grpc\SelectRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/reindexer.grpc.Reindexer/Select',
        $argument,
        ['\Reindexer\Grpc\QueryResultsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\UpdateRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Update(\Reindexer\Grpc\UpdateRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/reindexer.grpc.Reindexer/Update',
        $argument,
        ['\Reindexer\Grpc\QueryResultsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\DeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Delete(\Reindexer\Grpc\DeleteRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/reindexer.grpc.Reindexer/Delete',
        $argument,
        ['\Reindexer\Grpc\QueryResultsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\GetMetaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetMeta(\Reindexer\Grpc\GetMetaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/GetMeta',
        $argument,
        ['\Reindexer\Grpc\MetadataResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\PutMetaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PutMeta(\Reindexer\Grpc\PutMetaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/PutMeta',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\EnumMetaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function EnumMeta(\Reindexer\Grpc\EnumMetaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/EnumMeta',
        $argument,
        ['\Reindexer\Grpc\MetadataKeysResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\GetProtobufSchemaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetProtobufSchema(\Reindexer\Grpc\GetProtobufSchemaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/GetProtobufSchema',
        $argument,
        ['\Reindexer\Grpc\ProtobufSchemaResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\BeginTransactionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BeginTransaction(\Reindexer\Grpc\BeginTransactionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/BeginTransaction',
        $argument,
        ['\Reindexer\Grpc\TransactionIdResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\BidiStreamingCall
     */
    public function AddTxItem($metadata = [], $options = []) {
        return $this->_bidiRequest('/reindexer.grpc.Reindexer/AddTxItem',
        ['\Reindexer\Grpc\ErrorResponse','decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\CommitTransactionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CommitTransaction(\Reindexer\Grpc\CommitTransactionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/CommitTransaction',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Reindexer\Grpc\RollbackTransactionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RollbackTransaction(\Reindexer\Grpc\RollbackTransactionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/reindexer.grpc.Reindexer/RollbackTransaction',
        $argument,
        ['\Reindexer\Grpc\ErrorResponse', 'decode'],
        $metadata, $options);
    }

}
