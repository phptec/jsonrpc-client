<?php

namespace PhpTec\JsonRpc\Client\Test\Authentication;

use PhpTec\JsonRpc\Client\Authentication\BasicAuth;

class BasicAuthTest extends AuthenticationTestCase
{
    public function testAuthenticate(): void
    {
        $request = $this->createRequest();

        $username = 'test';
        $password = 'secret';
        $authentication = new BasicAuth($username, $password);

        $request = $authentication->authenticate($request);

        $this->assertSame('Basic dGVzdDpzZWNyZXQ=', $request->getHeaderLine('Authorization'));
    }
}