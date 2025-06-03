<?php

use NilPortugues\Stash\Driver\Predis;
use Predis\Client;
use PHPUnit\Framework\TestCase;

class PredisTest extends TestCase
{
    private Client $predisClient;
    private Predis $predisDriver;

    protected function setUp(): void
    {
        $this->predisClient = new Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);
        $this->predisDriver = new Predis($this->predisClient);
    }

    public function testStoreAndGetData(): void
    {
        $key = ['testKey'];
        $data = 'testData';
        $expiration = time() + 3600; // Expires in 1 hour

        $this->predisDriver->storeData($key, $data, $expiration);
        $retrievedData = $this->predisDriver->getData($key);

        $this->assertEquals($data, $retrievedData['data']);
        $this->assertEquals($expiration, $retrievedData['expiration']);
    }

    public function testClearData(): void
    {
        $key = ['testKey'];
        $data = 'testData';
        $expiration = time() + 3600;

        $this->predisDriver->storeData($key, $data, $expiration);
        $this->predisDriver->clear($key);
        $retrievedData = $this->predisDriver->getData($key);

        $this->assertFalse($retrievedData);
    }

    protected function tearDown(): void
    {
        $this->predisClient->flushdb();
        $this->predisClient->disconnect();
    }
}
