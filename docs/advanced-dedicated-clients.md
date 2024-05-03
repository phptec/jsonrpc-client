Dedicated Clients
=================

You can create a JSON-RPC client dedicated to specific API provider, e.g. - an SDK.


Inheritance <span id="inheritance"></span>
-----------

You may extend `\PhpTec\JsonRpc\Client\Client` class, adding methods declaration as wrap over `invoke()`.

```php
<?php

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;
use PhpTec\JsonRpc\Client\Client;

class MathRpc extends Client
{
    // `__construct()` is exempt from the usual signature compatibility rules when being extended:
    public function __construct(string $username, string $password)
    {
        parent::__construct('http://math.test/json-rpc');
        
        $this->setAuthentication(new BasicAuth($username, $password));
    }
    
    // create class method per each remote method:
    
    public function sum(...$args)
    {
        return $this->invoke('sum', $args);
    }
    
    public function pow($number, $exponent)
    {
        return $this->invoke('pow', [
            'number' => $number,
            'exponent' => $exponent,
        ]);
    }
    
    // ...
}

$math = new MathRpc('apiuser', 'secret');

$result = $math->sum(1, 5, 9);
var_dump($result); // outputs: (int) 15

$result = $math->pow(2, 4);
var_dump($result); // outputs: (int) 16
```

Since `\PhpTec\JsonRpc\Client\Client` already provides ability to invoke remote methods by name as its own.
You may simply use PHPDoc to provide static analysis support. For example:

```php
<?php

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;
use PhpTec\JsonRpc\Client\Client;

/**
 * @method int|float sum(array $params)
 * @method int|float pow(int|float $number, int|float $exponent)
 * ...
 */
class MathRpc extends Client
{
    // `__construct()` is exempt from the usual signature compatibility rules when being extended:
    public function __construct(string $username, string $password)
    {
        parent::__construct('http://math.test/json-rpc');
        
        $this->setAuthentication(new BasicAuth($username, $password));
    }
}

$math = new MathRpc('apiuser', 'secret');

$result = $math->sum([1, 5, 9]);
var_dump($result); // outputs: (int) 15

$result = $math->pow(number: 2, exponent: 4);
var_dump($result); // outputs: (int) 16
```


Composition <span id="composition"></span>
-----------

You can wrap `\PhpTec\JsonRpc\Client\Client` instance into your own class, declaring methods as a wrap over `invoke()`.
For example:

```php
<?php

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;
use PhpTec\JsonRpc\Client\Client;

class MathRpc
{
    private $jsonRpcClient;
    
    // instantiate JSON-RPC client as internal field:
    public function __construct(string $username, string $password)
    {
        $this->jsonRpcClient = Client::new('http://math.test/json-rpc')
            ->setAuthentication(new BasicAuth($username, $password));
    }
    
    // create class method per each remote method:
    
    public function sum(...$args)
    {
        return $this->jsonRpcClient->invoke('sum', $args);
    }
    
    public function pow($number, $exponent)
    {
        return $this->jsonRpcClient->invoke('pow', [
            'number' => $number,
            'exponent' => $exponent,
        ]);
    }
    
    // ...
}

$math = new MathRpc('apiuser', 'secret');

$result = $math->sum(1, 5, 9);
var_dump($result); // outputs: (int) 15

$result = $math->pow(2, 4);
var_dump($result); // outputs: (int) 16
```
