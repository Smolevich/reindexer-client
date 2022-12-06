<?php

use Reindexer\Grpc\ReindexerClient;
use Tests\Unit\Reindexer\BaseTest;

class GrpcTest extends BaseTest {
    private string $database = 'grpc_tests';
    private ReindexerClient $client;

    public function setUp(): void {
        $grpcHost = getenv('REINDEXER_GRPC_HOST');
        $this->client = new ReindexerClient($grpcHost, [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);
    }

    public function tearDown(): void
    {
        $this->client->Delete(new \Reindexer\Grpc\DeleteRequest(['dbName' => $this->database]));
    }

    public function testEnumDatabases()
    {
        $request = new \Reindexer\Grpc\CreateDatabaseRequest(['dbName' => $this->database]);
        list($response, $error) = $this->client->CreateDatabase($request)->wait();
        $request = new \Reindexer\Grpc\EnumDatabasesRequest();
        list($response, $error) = $this->client->enumDatabases($request)->wait();
        $this->assertIsObject($error);
        $this->assertSame(0, $error->code);
        $this->assertNotNull($response);
        $this->assertNotNull($response->getNames());
        $this->assertIsObject($response->getNames());
        $this->assertSame('unittests', $response->getNames()[0]);
    }
}
