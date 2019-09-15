<?php

namespace Tests\Feature\Reindexer;

use Reindexer\Client\Api;
use Reindexer\Services\Database;
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

    public function setUp() {
        $host = getenv('REINDEXER_HOST');
        $this->config = [
            'host' => $host
        ];
        $this->api = new Api($this->config['host']);
        $this->dbService = new Database($this->api);
        $this->nsService = new Namespaces($this->api);
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
}
