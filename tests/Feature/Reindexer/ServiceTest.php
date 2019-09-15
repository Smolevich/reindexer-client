<?php

namespace Tests\Feature\Reindexer;

use Reindexer\Client\Api;
use Reindexer\Entities\Index;
use Reindexer\Services\Database;
use Reindexer\Services\Index as ReindexerIndex;
use Reindexer\Services\Namespaces;
use Tests\Unit\Reindexer\BaseTest;

class ServiceTest extends BaseTest {
    private $namespaceName = 'unittests_ns';
    private $database = 'unittests';
    /**
     *
     * @var Namespaces $nsService
     */
    private $nsService;
    /**
     *
     * @var Database $dbService
     */
    private $dbService;

    /**
     *
     * @var ReindexerIndex $indexService
     */
    private $indexService;

    public function setUp() {
        $host = getenv('REINDEXER_HOST');
        $this->config = [
            'host' => $host
        ];
        $this->api = new Api($this->config['host']);
        $this->dbService = new Database($this->api);
        $this->nsService = new Namespaces($this->api);
        $this->indexService = new ReindexerIndex($this->api);
        $this->nsService->setDatabase($this->database);
        $this->dbService->create($this->database);
        $this->nsService->create($this->namespaceName);
    }

    public function tearDown() {
        $this->dbService->drop($this->database);
    }

    public function testCreateAndDropDatabase() {
        $response = $this->dbService->create('unittests_2');
        $this->assertSame(
            [
                'success' => true,
                'response_code' => 200,
                'description' => ''
            ],
            $response->getDecodedResponseBody(true)
        );
        $this->dbService->drop('unittests_2');
        $databases = $this->dbService->getList()->getDecodedResponseBody(true);
        $this->assertEquals(1, count($databases['items']));
    }

    public function testCreateIndexes() {
        $indexId = (new Index())
            ->setCollateMode('none')
            ->setName('id')
            ->setIsPk(true)
            ->setIndexType(\Reindexer\Enum\IndexType::HASH)
            ->setFieldType('int')
            ->setJsonPaths(['id'])
            ->setIsDense(true);
        $this->indexService->create($indexId, $this->database, $this->namespaceName);
        $body = $this->indexService->get($this->database, $this->namespaceName)->getDecodedResponseBody(true);
        $this->assertEquals(1, $body['total_items']);
        $this->assertEquals($indexId->getName(), $body['items'][0]['name']);
        $this->assertEquals($indexId->isPk(), $body['items'][0]['is_pk']);
        $this->assertEquals($indexId->getIndexType(), $body['items'][0]['index_type']);
        $this->assertEquals($indexId->getJsonPaths(), $body['items'][0]['json_paths']);
        $this->assertEquals($indexId->isDense(), $body['items'][0]['is_dense']);
        $this->assertEquals($indexId->getCollateMode(), $body['items'][0]['collate_mode']);
    }
}
