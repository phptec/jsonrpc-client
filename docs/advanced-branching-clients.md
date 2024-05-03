Branching Clients
=================

A particular API service may provide numerous of remote methods.
For example a "mass mail" service API may provide methods for working with users (recipients), email templates,
campaigns and other entities. Such service may have the following list of RPC methods:

* 'users.all'
* 'users.one'
* 'users.create'
* 'users.update'
* 'users.delete'
* 'templates.all'
* 'templates.one'
* 'templates.create'
* 'templates.update'
* 'templates.delete'
* 'campaigns.all'
* 'campaigns.one'
* 'campaigns.create'
* 'campaigns.update'
* 'campaigns.delete'

and so on.

In order to avoid overhead and simplify your code you may create branch (fork) from JSON-RPC client, which can be dedicated
to the specific domain within the external API. For example:

```php
<?php

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;
use PhpTec\JsonRpc\Client\Client;

$massMailClient = Client::new('https://mass-mail.test/json-rpc')
    ->setAuthentication(new BasicAuth('apiuser', 'secret'));

$userRepository = $massMailClient->clone() // clone base client re-using its configuration
    ->setMethodPrefix('users.'); // add prefix, which should be added to each method name

$templateRepository = $massMailClient->clone() // clone base client re-using its configuration
    ->setMethodPrefix('templates.'); // add prefix, which should be added to each method name

$newUserId = $userRepository->create(
    name: 'John Doe',
    email: 'johndoe@example.test'
);
// JSON behind the scenes:
// --> `{"jsonrpc": "2.0", "method": "users.create", "params": {"name": "John Doe", "email": "johndoe@example.test"}, "id": 1}`
// <-- {"jsonrpc": "2.0", "result": 9876, "id": 1}

$newTemplateId = $templateRepository->create(
    content: 'Template content'
);
// JSON behind the scenes:
// --> `{"jsonrpc": "2.0", "method": "templates.create", "params": {"content": "Template content"}, "id": 1}`
// <-- {"jsonrpc": "2.0", "result": 8765, "id": 1}
```
