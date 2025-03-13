<?php

namespace PhpTec\JsonRpc\Client\Test;

use GuzzleHttp\Psr7\Response;
use PhpTec\JsonRpc\Client\Authentication\Header;
use PhpTec\JsonRpc\Client\Client;
use PhpTec\JsonRpc\Client\Rpc;
use PhpTec\JsonRpc\Client\Test\Support\Logger;
use PHPUnit\Framework\TestCase;

class ClientRequestTest extends TestCase
{
    /**
     * @var \Http\Mock\Client
     */
    protected $httpClient;

    /**
     * @var \PhpTec\JsonRpc\Client\Client
     */
    protected $rpcClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new \Http\Mock\Client();

        $this->rpcClient = (new Client('http://example.com/json-rpc'))
            ->setHttpClient($this->httpClient);
    }

    public function testInvoke(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        $result = $this->rpcClient->invoke('foo', ['name' => 'bar']);

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('application/json', $lastRequest->getHeaderLine('Content-Type'));

        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertSame('2.0', $bodyJson['jsonrpc']);
        $this->assertFalse(empty($bodyJson['id']));
        $this->assertSame('foo', $bodyJson['method']);
        $this->assertEquals(['name' => 'bar'], $bodyJson['params']);
    }

    /**
     * @depends testInvoke
     */
    public function testInvokeRpc(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');
        $this->httpClient->addResponse($httpResponse);

        $result = $this->rpcClient->invokeRpc(new Rpc('foo', ['name' => 'bar']));

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('application/json', $lastRequest->getHeaderLine('Content-Type'));

        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertSame('2.0', $bodyJson['jsonrpc']);
        $this->assertFalse(empty($bodyJson['id']));
        $this->assertSame('foo', $bodyJson['method']);
        $this->assertEquals(['name' => 'bar'], $bodyJson['params']);
    }

    public function testInvokeRpcBatch(): void
    {
        $httpResponse = new Response(200, [], json_encode([
            [
                'jsonrpc' => '2.0',
                'id' => 'bar',
                'result' => 'success-bar',
            ],
            [
                'jsonrpc' => '2.0',
                'id' => 'foo',
                'result' => 'success-foo',
            ],
        ]));
        $this->httpClient->addResponse($httpResponse);

        $results = $this->rpcClient->invokeBatch([
            'foo' => new Rpc('method-foo', ['name' => 'foo']),
            'bar' => new Rpc('method-bar', ['name' => 'bar']),
        ]);

        $this->assertSame('success-foo', $results['foo']);
        $this->assertSame('success-bar', $results['bar']);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('application/json', $lastRequest->getHeaderLine('Content-Type'));

        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertFalse(empty($bodyJson[0]));
        $this->assertFalse(empty($bodyJson[1]));

        $this->assertSame('2.0', $bodyJson[0]['jsonrpc']);
        $this->assertFalse(empty($bodyJson[0]['id']));
        $this->assertSame('method-foo', $bodyJson[0]['method']);
        $this->assertEquals(['name' => 'foo'], $bodyJson[0]['params']);
    }

    public function testInvokeRpcBatchAsArray(): void
    {
        $httpResponse = new Response(200, [], json_encode([
            [
                'jsonrpc' => '2.0',
                'id' => 'bar',
                'result' => 'success-bar',
            ],
            [
                'jsonrpc' => '2.0',
                'id' => 'foo',
                'result' => 'success-foo',
            ],
        ]));
        $this->httpClient->addResponse($httpResponse);

        $results = $this->rpcClient->invokeBatch([
            'foo' => [
                'method-foo' => ['name' => 'foo'],
            ],
            'bar' => [
                'method-bar' => ['name' => 'bar'],
            ],
        ]);

        $this->assertSame('success-foo', $results['foo']);
        $this->assertSame('success-bar', $results['bar']);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('application/json', $lastRequest->getHeaderLine('Content-Type'));

        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertFalse(empty($bodyJson[0]));
        $this->assertFalse(empty($bodyJson[1]));

        $this->assertSame('2.0', $bodyJson[0]['jsonrpc']);
        $this->assertFalse(empty($bodyJson[0]['id']));
        $this->assertSame('method-foo', $bodyJson[0]['method']);
        $this->assertEquals(['name' => 'foo'], $bodyJson[0]['params']);
    }

    /**
     * @depends testInvoke
     */
    public function testMagicCall(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        $result = $this->rpcClient->foo(['name' => 'bar']);

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();
        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertSame('foo', $bodyJson['method']);
        $this->assertEquals(['name' => 'bar'], $bodyJson['params']);
    }

    /**
     * @depends testMagicCall
     */
    public function testMagicCallEnumerableArgs(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":6}');

        $this->httpClient->addResponse($httpResponse);

        $result = $this->rpcClient->sum(1, 2, 3);

        $this->assertSame(6, $result);

        $lastRequest = $this->httpClient->getLastRequest();
        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertSame('sum', $bodyJson['method']);
        $this->assertEquals([1, 2, 3], $bodyJson['params']);
    }

    /**
     * @depends testMagicCall
     */
    public function testMagicCallNamedArguments(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $this->markTestSkipped('PHP version >= 8.0.0 required.');
        }

        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        // avoid parse error on PHP < 8.0
        eval(
<<<'PHP'
$result = $this->rpcClient->foo(name: 'bar');
PHP
        );

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();
        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertSame('foo', $bodyJson['method']);
        $this->assertEquals(['name' => 'bar'], $bodyJson['params']);
    }

    /**
     * @depends testInvoke
     */
    public function testAuthenticate(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        $this->rpcClient->setAuthentication(new Header('X-Auth', 'Secret'));

        $result = $this->rpcClient->invoke('foo', ['name' => 'bar']);

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('Secret', $lastRequest->getHeaderLine('X-Auth'));
    }

    /**
     * @depends testInvoke
     */
    public function testLogging(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        $logger = new Logger();

        $this->rpcClient->setLogger($logger);

        $result = $this->rpcClient->invoke('foo', ['name' => 'bar']);

        $this->assertSame('success', $result);

        $this->assertNotEmpty($logger->logs);
        $this->assertSame('foo', $logger->logs[0]['context']['request']['method']);
        $this->assertSame('success', $logger->logs[0]['context']['response']['result']);
    }

    /**
     * @depends testInvoke
     */
    public function testMethodPrefix(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        $this->rpcClient->setMethodPrefix('prefix.');

        $result = $this->rpcClient->invoke('foo', ['name' => 'bar']);

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();

        $bodyJson = json_decode($lastRequest->getBody()->__toString(), true);

        $this->assertSame('prefix.foo', $bodyJson['method']);
    }

    public function testHttpError(): void
    {
        $httpResponse = new Response(500, [], '<h1>Test Error Message</h1>');

        $this->httpClient->addResponse($httpResponse);

        try {
            $result = $this->rpcClient->invoke('foo', ['name' => 'bar']);
        } catch (\Exception $exception) {}

        $this->assertTrue(isset($exception));

        $this->assertSame(500, $exception->getCode());
        $this->assertSame('500 Internal Server Error: Test Error Message', $exception->getMessage());
    }

    /**
     * @depends testInvoke
     */
    public function testMethodQueryParam(): void
    {
        $httpResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"success"}');

        $this->httpClient->addResponse($httpResponse);

        $this->rpcClient->setMethodQueryParam('rpc');

        $result = $this->rpcClient->invoke('foo', ['name' => 'bar']);

        $this->assertSame('success', $result);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('rpc=foo', $lastRequest->getUri()->getQuery());
    }

    /**
     * @depends testMethodQueryParam
     * @depends testInvokeRpcBatch
     */
    public function testMethodQueryParamWithBatchRequest(): void
    {
        $httpResponse = new Response(200, [], json_encode([
            [
                'jsonrpc' => '2.0',
                'id' => 'bar',
                'result' => 'success-bar',
            ],
            [
                'jsonrpc' => '2.0',
                'id' => 'foo',
                'result' => 'success-foo',
            ],
        ]));
        $this->httpClient->addResponse($httpResponse);

        $this->rpcClient->setMethodQueryParam('rpc');

        $results = $this->rpcClient->invokeBatch([
            'foo' => new Rpc('method-foo', ['name' => 'foo']),
            'bar' => new Rpc('method-bar', ['name' => 'bar']),
        ]);

        $lastRequest = $this->httpClient->getLastRequest();

        $this->assertSame('rpc%5Bfoo%5D=method-foo&rpc%5Bbar%5D=method-bar', $lastRequest->getUri()->getQuery());
    }
}