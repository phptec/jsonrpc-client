Batch Invocation
================

JSON-RPC protocol allows invocation of several remote methods via single HTTP request.
Using this feature may significantly reduce the execution time of your program.

Use `invokeBatch()` method to invoke several remote methods at once. For example:

```php
<?php

use PhpTec\JsonRpc\Client\Client;

$jsonRpcClient = new Client('https://example.test/json-rpc');

$results = $jsonRpcClient->invokeBatch([
    'first' => ['subtract' => [42, 23]],
    'second' => ['subtract' => ['minuend' => 14, 'subtrahend' => 9]],
    'third' => ['sum' => [12, 14]],
]);

var_dump($results); // outputs: `array('first' => 19, 'second' => 5, 'third' => 26)`

/* JSON behind the scenes:
--> [
    {"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": "first"},
    {"jsonrpc": "2.0", "method": "subtract", "params": ["minuend": 42, "subtrahend": 23], "id": "second"},
    {"jsonrpc": "2.0", "method": "third", "params": [12, 14], "id": "third"}
]
<-- [
    {"jsonrpc": "2.0", "result": 19, "id": "first"},
    {"jsonrpc": "2.0", "result": 5, "id": "second"},
    {"jsonrpc": "2.0", "result": 26, "id": "third"}
]
*/
```

You may also use `\PhpTec\JsonRpc\Client\Rpc` DTO. For example:

```php
<?php

use PhpTec\JsonRpc\Client\Client;
use PhpTec\JsonRpc\Client\Rpc;

$jsonRpcClient = new Client('https://example.test/json-rpc');

$results = $jsonRpcClient->invokeBatch([
    'first' => Rpc::new('subtract', [42, 23]),
    'second' => Rpc::new('subtract', ['minuend' => 14, 'subtrahend' => 9]),
    'third' => Rpc::new('sum', [12, 14]),
]);

var_dump($results); // outputs: `array('first' => 19, 'second' => 5, 'third' => 26)`
```

> Note: keep in mind that in real world the JSON-RPC API providers may not support batch requests, or limit the amount
  of methods, which could be invoked per single request.
