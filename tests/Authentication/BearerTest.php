<?php

namespace PhpTec\JsonRpc\Client\Test\Authentication;

use PhpTec\JsonRpc\Client\Authentication\Bearer;

class BearerTest extends AuthenticationTestCase
{
    public function testAuthenticate(): void
    {
        $request = $this->createRequest();

        $token = 'test-token';
        $authentication = new Bearer($token);

        $request = $authentication->authenticate($request);

        $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
    }
}