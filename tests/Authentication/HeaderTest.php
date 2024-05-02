<?php

namespace PhpTec\JsonRpc\Client\Test\Authentication;

use PhpTec\JsonRpc\Client\Authentication\Header;

class HeaderTest extends AuthenticationTestCase
{
    public function testAuthenticate(): void
    {
        $request = $this->createRequest();

        $name = 'X-Test-Auth';
        $value = 'Secret';
        $authentication = new Header($name, $value);

        $request = $authentication->authenticate($request);

        $this->assertSame($value, $request->getHeaderLine($name));
    }
}