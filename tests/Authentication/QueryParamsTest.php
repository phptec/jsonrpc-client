<?php

namespace PhpTec\JsonRpc\Client\Test\Authentication;

use PhpTec\JsonRpc\Client\Authentication\QueryParams;

class QueryParamsTest extends AuthenticationTestCase
{
    public function testAuthenticate(): void
    {
        $request = $this->createRequest();

        $params = [
            'username' => 'test',
            'password' => 'secret',
        ];
        $authentication = new QueryParams($params);

        $request = $authentication->authenticate($request);

        $this->assertSame('username=test&password=secret', $request->getUri()->getQuery());
    }
}