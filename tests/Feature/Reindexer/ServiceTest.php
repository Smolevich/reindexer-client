<?php

namespace Tests\Feature\Reindexer;

use Reindexer\Client\Api;
use Reindexer\Entities\Index;
use Reindexer\Enum\IndexType;
use Reindexer\Response;
use Reindexer\Services\Database;
use Reindexer\Services\Index as ReindexerIndex;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;
use Tests\Feature\Reindexer\Fixture\HabrPost;
use Tests\Unit\Reindexer\BaseTest;

class ServiceTest extends BaseTest
{
    private string $namespaceName = 'unittests_ns';
    private string $database = 'unittests';
    private Namespaces $nsService;
    private Database $dbService;

    private ReindexerIndex $indexService;
    private Query $queryService;
    private Item $itemService;

    public function setUp(): void
    {
        $host = getenv('REINDEXER_HOST');
        $this->config = [
            'host' => $host
        ];
        $this->api = new Api($this->config['host']);
        $this->dbService = new Database($this->api);
        $this->nsService = new Namespaces($this->api);
        $this->indexService = new ReindexerIndex($this->api);
        $this->queryService = new Query($this->api);
        $this->queryService->setDatabase($this->database);
        $this->itemService = new Item($this->api);
        $this->itemService->setNamespace($this->namespaceName);
        $this->itemService->setDatabase($this->database);
        $this->nsService->setDatabase($this->database);
        $this->dbService->create($this->database);
        $this->nsService->create($this->namespaceName);
    }

    public function tearDown(): void
    {
        $this->dbService->drop($this->database);
    }

    public function testCreateAndDropDatabase()
    {
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

    public function testCreateIndexes()
    {
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

    public function testQuery()
    {
        $items = HabrPost::DATA;
        $indexLink = (new Index())
            ->setCollateMode('none')
            ->setName('link')
            ->setIndexType(IndexType::TEXT)
            ->setFieldType('string')
            ->setJsonPaths(['link']);
        $indexUserNickname = (new Index())
            ->setCollateMode('none')
            ->setName('user_nickname')
            ->setIndexType(IndexTYpe::HASH)
            ->setFieldType('string')
            ->setJsonPaths(['user_nickname']);
        $indexId = (new Index())
            ->setCollateMode('none')
            ->setName('id')
            ->setIsPk(true)
            ->setIndexType(IndexType::HASH)
            ->setFieldType('int')
            ->setJsonPaths(['id'])
            ->setIsDense(true);
        $this->indexService->create($indexLink, $this->database, $this->namespaceName);
        $this->indexService->create($indexId, $this->database, $this->namespaceName);
        $this->indexService->create($indexUserNickname, $this->database, $this->namespaceName);
        foreach ($items as $index => $item) {
            $item['id'] = $index;
            $response = $this->itemService->add($item);
            $this->assertEquals(200, $response->getCode());
        }

        $response = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->namespaceName} WHERE user_nickname = 'LMonoceros'")
            ->getDecodedResponseBody(true);
        $this->assertEquals(2, count($response['items']));

        $response = $this->queryService
            ->createByHttpGet("SELECT * FROM {$this->namespaceName} WHERE rating > 446");

        $this->assertEquals(3, count($response->getDecodedResponseBody(true)['items']));
        $this->assertEquals(3, count($response->getDecodedResponseBody()->items));
    }


    public function testAddHeaders()
    {
        $headers = ['User-Agent' => 'reindexer-php-client'];
        $this->dbService->addHeaders($headers);
        $response = $this->dbService->create('unittests_3');
        $this->assertSame(
            [
                'success' => true,
                'response_code' => 200,
                'description' => ''
            ],
            $response->getDecodedResponseBody(true)
        );
        $this->dbService->drop('unittests_3');
        $this->assertSame(
            $headers['User-Agent'],
            $response->getRequestHeaders()['User-Agent'][0]
        );
        $this->assertCount(3, $response->getRequestHeaders());
    }

    public function testSetHeaders()
    {
        $headers = ['User-Agent' => 'reindexer-php-client'];
        $this->dbService->setHeaders($headers);
        $response = $this->dbService->create('unittests_4');
        $this->assertSame(
            [
                'success' => true,
                'response_code' => 200,
                'description' => ''
            ],
            $response->getDecodedResponseBody(true)
        );
        $this->dbService->drop('unittests_4');
        $this->assertSame(
            $headers['User-Agent'],
            $response->getRequestHeaders()['User-Agent'][0]
        );
        $this->assertCount(2, $response->getRequestHeaders());
    }

    public function testSchema()
    {
        $config = [
            'type' =>  'namespaces',
            'namespaces' => [
                'wal_size' => 5000000
            ]
        ];
        $response = $this->nsService->schema($this->namespaceName, $config);
        $this->assertSame(
            [
                'success' => true,
                'response_code' => 200,
                'description' => ''
            ],
            $response->getDecodedResponseBody(true)
        );
    }

    public function testSetReplication(): Response
    {
        $config = [
            'type' => 'namespaces',
            'replication' => [
                'role' => 'master',
                'cluster_id' => 2,
                'server_id' =>  0
            ]
        ];
        $this->itemService->setNamespace(urlencode('#config'));
        $response = $this->itemService->update($config);
        $this->assertSame(
            [
                'success' => true,
                'updated' => 1,
            ],
            $response->getDecodedResponseBody(true)
        );

    }
}
