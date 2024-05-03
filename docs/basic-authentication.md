Authentication
==============

You can specify the authentication strategy using `setAuthentication()` method.
For example:

```php
<?php

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;
use PhpTec\JsonRpc\Client\Client;

$jsonRpcClient = Client::new('https://example.test/json-rpc')
    ->setAuthentication(new BasicAuth('apiuser', 'secret'));
```

The following authentication strategies are available:

* [BasicAuth](../src/Authentication/BasicAuth.php)
* [Bearer](../src/Authentication/Bearer.php)
* [Header](../src/Authentication/Header.php)
* [QueryParams](../src/Authentication/QueryParams.php)

Please refer to the particular class for more details.

You can always create your own authentication strategy implementing `\PhpTec\JsonRpc\Client\AuthenticationContract` interface.
