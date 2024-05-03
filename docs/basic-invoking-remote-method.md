Invoking Remote Method
======================

Basic invocation <span id="basic-invocation"></span>
----------------

Once `\PhpTec\JsonRpc\Client\Client` instance is configured you can use to invoke remote API methods.
For example:

```php
<?php

use PhpTec\JsonRpc\Client\Client;

$jsonRpcClient = new Client('https://example.test/json-rpc');

$result = $jsonRpcClient->invoke('subtract', [42, 23]); 
var_dump($result); // outputs: `(int) 19`
// JSON behind the scenes:
// --> `{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}`
// <-- {"jsonrpc": "2.0", "result": 19, "id": 1}

$result = $jsonRpcClient->invoke('subtract', ['minuend' => 42, 'subtrahend' => 23]); 
var_dump($result); // outputs: `(int) 19`
// JSON behind the scenes:
// --> `{"jsonrpc": "2.0", "method": "subtract", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}`
// <-- {"jsonrpc": "2.0", "result": 19, "id": 1}
```


Using DTO <span id="using-dto"></span>
---------

You may also use `\PhpTec\JsonRpc\Client\Rpc` DTO for method invocation. For example:

```php
<?php

use PhpTec\JsonRpc\Client\Rpc;

$result = $jsonRpcClient->invokeRpc(new Rpc('subtract', [42, 23])); 
var_dump($result); // outputs: `(int) 19`

$result = $jsonRpcClient->invokeRpc(
    Rpc::new()
        ->setMethod('subtract')
        ->setParams([42, 23])
); 
var_dump($result); // outputs: `(int) 19`
```


Direct invocation <span id="direct-invocation"></span>
-----------------

Also, you can invoke the remote method name directly from client instance as it has been defined inside of it.
For example:

```php
<?php

$result = $jsonRpcClient->subtract([42, 23]);
var_dump($result); // outputs: `(int) 19`

$result = $jsonRpcClient->subtract([
    'minuend' => 42,
    'subtrahend' => 23,
]);
var_dump($result); // outputs: `(int) 19`
```

In case you are using PHP 8.0 or higher and all remote method arguments are named one, you can use PHP named parameters
invocation syntax. For example:

```php
<?php

$result = $jsonRpcClient->subtract(
    minuend: 42,
    subtrahend: 23
);
var_dump($result); // outputs: `(int) 19`
```
