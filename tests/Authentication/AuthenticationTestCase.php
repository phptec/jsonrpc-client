<?php

namespace PhpTec\JsonRpc\Client\Test\Authentication;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

abstract class AuthenticationTestCase extends TestCase
{
    protected function createRequest(): RequestInterface
    {
        $httpRequestFactory = new HttpFactory();

        return $httpRequestFactory->createRequest('POST', 'https://example.com/json-rpc');
    }

    abstract public function testAuthenticate(): void;
}