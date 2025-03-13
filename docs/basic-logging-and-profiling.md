Logging and Profiling
=====================

This package supports [PSR-3 compatible logger](https://www.php-fig.org/psr/psr-3/) for logging remote methods requests.
Use `setLogger()` method to configure logger for the JSON-RPC client. For example: if you are using [monolog/monolog](https://packagist.org/packages/monolog/monolog)
package, your code may look like following:

```php
<?php

use PhpTec\JsonRpc\Client\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Level;

// prepare logger:
$logger = new Logger('json-rpc');
$logger->pushHandler(new StreamHandler('path/to/your.log', Level::Debug));

// create JSON-PRC client with logger:
$jsonRpcClient = Client::new('https://example.test/json-rpc')
    ->setLogger($logger);
```

Per each HTTP request a new log entry with level `\Psr\Log\LogLevel::DEBUG` will be added.
The context for this entry contains request and response data, endpoint and execution time.


HTTP Logs <span id="http-logs"></span>
---------

One of the drawback of JSON-RPC protocol is that it uses HTTP message body (POST) to transfer all significant request information.
Thus, if you navigate over web server access logs on the JSON-RPC API provider you will see something like this:

```
47.29.201.179 - - [28/Feb/2025:13:17:10 +0000] "GET /json-rpc HTTP/1.2" 200 5316 "https://example.test/json-rpc" "JSON RPC Client"
47.29.201.179 - - [28/Feb/2025:13:16:29 +0000] "GET /json-rpc HTTP/1.2" 200 5316 "https://example.test/json-rpc" "JSON RPC Client"
...
```

In such log there is no way of knowing which JSON-RPC method was invoked with particular API call.
If some request causes trouble it is hard to link it with the particular program source code.

You can use `setDebugMethodQueryParam()` method to add extra query string (GET) parameter to all outgoing JSON-RPC
requests, which will contain the name of JSON-RPC method invoked. For example:

```php
<?php

use PhpTec\JsonRpc\Client\Client;

// create JSON-PRC client and enable debugging of method name via HTTP query string:
$jsonRpcClient = Client::new('https://example.test/json-rpc')
    ->setDebugMethodQueryParam('m');

$result = $jsonRpcClient->invoke('subtract', ['minuend' => 42, 'subtrahend' => 23]); // actual request URL: 'https://example.test/json-rpc?m=subtract'
```

With such settings web server access logs will look like following:

```
47.29.201.179 - - [28/Feb/2025:13:17:10 +0000] "GET /json-rpc?m=subtract HTTP/1.2" 200 5316 "https://example.test/json-rpc?m=subtract" "JSON RPC Client"
47.29.201.179 - - [28/Feb/2025:13:16:29 +0000] "GET /json-rpc?m=pow HTTP/1.2" 200 5316 "https://example.test/json-rpc?m=pow" "JSON RPC Client"
...
```

> Note: choose the name for the debug query parameter wisely: make sure it does not conflict with any other parameter
  like auth specification. Also keep in mind that some API providers may validate incoming request against extra or
  misspelled parameters and trigger an error if something unexpected has been passed. You may consider use one of the
  UTM parameter names like 'utm_term' for debugging, as it is usually excluded from the validation.
