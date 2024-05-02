Installation
============

Composer setup <span id="composer-setup"></span>
--------------

The preferred way to install this package is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist phptec/jsonrpc-client
```

or add

```json
"phptec/jsonrpc-client": "*"
```

to the "require" section of your composer.json.

Keep in mind that this package requires the [PSR-18 compatible HTTP client](https://www.php-fig.org/psr/psr-18/) library
to function, which is not shipped by default. You can use any library you want, but usage of
[guzzlehttp/guzzle](https://packagist.org/packages/guzzlehttp/guzzle) is recommended. 
Its classes are used for the HTTP transporting support by default, and you can omit an HTTP layer configuring.

Thus, in the end your `composer.json` should look like following:

```json
{
    "name": "my/project",
    ...
    "require": {
        "phptec/jsonrpc-client": "^1.0",
        "guzzlehttp/guzzle": "^7.0",
        ...
    },
    ...
}
```

> Note: the code examples provided further in this documentation are written in assumption you have "guzzlehttp/guzzle"
  package installed.


Instantiating JSON-RPC Client <span id="instantiating-json-rpc-client"></span>
----------------------------

```php
<?php

use PhpTec\JsonRpc\Client\Client;

$jsonRpcClient = new Client('https://example.test/json-rpc');
```