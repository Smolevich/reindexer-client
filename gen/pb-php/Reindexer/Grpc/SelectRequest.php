<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>reindexer.grpc.SelectRequest</code>
 */
class SelectRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     */
    protected $dbName = '';
    /**
     * Generated from protobuf field <code>.reindexer.grpc.Query query = 2;</code>
     */
    protected $query = null;
    /**
     * Generated from protobuf field <code>.reindexer.grpc.OutputFlags flags = 3;</code>
     */
    protected $flags = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $dbName
     *     @type \Reindexer\Grpc\Query $query
     *     @type \Reindexer\Grpc\OutputFlags $flags
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Reindexer::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setDbName($var)
    {
        GPBUtil::checkString($var, True);
        $this->dbName = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.Query query = 2;</code>
     * @return \Reindexer\Grpc\Query|null
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function hasQuery()
    {
        return isset($this->query);
    }

    public function clearQuery()
    {
        unset($this->query);
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.Query query = 2;</code>
     * @param \Reindexer\Grpc\Query $var
     * @return $this
     */
    public function setQuery($var)
    {
        GPBUtil::checkMessage($var, \Reindexer\Grpc\Query::class);
        $this->query = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.OutputFlags flags = 3;</code>
     * @return \Reindexer\Grpc\OutputFlags|null
     */
    public function getFlags()
    {
        return $this->flags;
    }

    public function hasFlags()
    {
        return isset($this->flags);
    }

    public function clearFlags()
    {
        unset($this->flags);
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.OutputFlags flags = 3;</code>
     * @param \Reindexer\Grpc\OutputFlags $var
     * @return $this
     */
    public function setFlags($var)
    {
        GPBUtil::checkMessage($var, \Reindexer\Grpc\OutputFlags::class);
        $this->flags = $var;

        return $this;
    }

}

