Logging and Profiling
=====================

This package supports [PSR-3 compatible logger](https://www.php-fig.org/psr/psr-3/) for logging remote methods requests.
Use `setLogger()` method to configure logger for the JSON-RPC client. For example: if you are using [monolog/monolog](https://packagist.org/packages/monolog/monolog)
package your code may look like following:

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
