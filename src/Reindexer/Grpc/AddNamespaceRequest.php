<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reindexer.proto

namespace Reindexer\Grpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>reindexer.grpc.AddNamespaceRequest</code>
 */
class AddNamespaceRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string dbName = 1;</code>
     */
    protected $dbName = '';
    /**
     * Generated from protobuf field <code>.reindexer.grpc.Namespace namespace = 2;</code>
     */
    protected $namespace = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $dbName
     *     @type \Reindexer\Grpc\PBNamespace $namespace
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
     * Generated from protobuf field <code>.reindexer.grpc.Namespace namespace = 2;</code>
     * @return \Reindexer\Grpc\PBNamespace|null
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    public function hasNamespace()
    {
        return isset($this->namespace);
    }

    public function clearNamespace()
    {
        unset($this->namespace);
    }

    /**
     * Generated from protobuf field <code>.reindexer.grpc.Namespace namespace = 2;</code>
     * @param \Reindexer\Grpc\PBNamespace $var
     * @return $this
     */
    public function setNamespace($var)
    {
        GPBUtil::checkMessage($var, \Reindexer\Grpc\PBNamespace::class);
        $this->namespace = $var;

        return $this;
    }

}
