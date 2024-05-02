Custom HTTP Client
==================

This library can function with any [PSR-18 compatible HTTP client](https://www.php-fig.org/psr/psr-18/) library.
All you need to do is setup HTTP components during configuration of `\PhpTec\JsonRpc\Client\Client` instance.
You'll the implementation of the following PSR interfaces:

* `\Psr\Http\Client\ClientInterface`
* `\Psr\Http\Message\RequestFactoryInterface`
* `\Psr\Http\Message\StreamFactoryInterface`

Let's assume you implemented these within you own codebase. Such implementation may look like following:

```php
<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

class MyHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // ...
    }
    
    // ...
}

class MyHttpRequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        // ...
    }
    
    // ...
}

class MyHttpStreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        // ...
    }
    
    // ...
}
```

With such codebase the configuration for the JSON-RPC client should be following:

```php
<?php

use PhpTec\JsonRpc\Client\Client;

$jsonRpcClient = Client::new('https://example.test/json-rpc')
    ->setHttpClient(new MyHttpClient())
    ->setHttpRequestFactory(new MyHttpRequestFactory())
    ->setHttpStreamFactory(new MyHttpStreamFactory());
```
