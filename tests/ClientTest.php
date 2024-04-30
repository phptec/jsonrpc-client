<?php

namespace PhpTec\JsonRpc\Test;

use PhpTec\JsonRpc\Client\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testSetUpHttpClient(): void
    {
        $client = new Client();

        $httpClient = new \GuzzleHttp\Client();

        $client->setHttpClient($httpClient);

        $this->assertSame($httpClient, $client->getHttpClient());
    }

    public function testGetDefaultHttpClient(): void
    {
        $client = new Client();

        $httpClient = $client->getHttpClient();

        $this->assertTrue($httpClient instanceof \Psr\Http\Client\ClientInterface);
    }
}