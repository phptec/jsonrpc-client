<?php

namespace PhpTec\JsonRpc\Client\Test;

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;
use PhpTec\JsonRpc\Client\Client;
use PhpTec\JsonRpc\Client\Test\Support\Logger;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testSetupHttpClient(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $httpClient = new \Http\Mock\Client();

        $client->setHttpClient($httpClient);

        $this->assertSame($httpClient, $client->getHttpClient());
    }

    /**
     * @depends testSetupHttpClient
     */
    public function testGetDefaultHttpClient(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $httpClient = $client->getHttpClient();

        $this->assertTrue($httpClient instanceof \Psr\Http\Client\ClientInterface);
    }

    public function testSetupHttpRequestFactory(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $httpRequestFactory = new \GuzzleHttp\Psr7\HttpFactory();

        $client->setHttpRequestFactory($httpRequestFactory);

        $this->assertSame($httpRequestFactory, $client->getHttpRequestFactory());
    }

    /**
     * @depends testSetupHttpRequestFactory
     */
    public function testGetDefaultHttpRequestFactory(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $httpRequestFactory = $client->getHttpRequestFactory();

        $this->assertTrue($httpRequestFactory instanceof \Psr\Http\Message\RequestFactoryInterface);
    }

    public function testSetupHttpStreamFactory(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $httpStreamFactory = new \GuzzleHttp\Psr7\HttpFactory();

        $client->setHttpStreamFactory($httpStreamFactory);

        $this->assertSame($httpStreamFactory, $client->getHttpStreamFactory());
    }

    /**
     * @depends testSetupHttpStreamFactory
     */
    public function testGetDefaultHttpStreamFactory(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $httpStreamFactory = $client->getHttpStreamFactory();

        $this->assertTrue($httpStreamFactory instanceof \Psr\Http\Message\StreamFactoryInterface);
    }

    public function testSetupAuthentication(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $authentication = new BasicAuth('test', 'secret');

        $client->setAuthentication($authentication);

        $this->assertSame($authentication, $client->getAuthentication());
    }

    public function testSetupLogger(): void
    {
        $client = new Client('http://example.com/json-rpc');

        $logger = new Logger();

        $client->setLogger($logger);

        $this->assertSame($logger, $client->getLogger());
    }
}