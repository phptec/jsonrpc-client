<?php

namespace PhpTec\JsonRpc\Client\Authentication;

use PhpTec\JsonRpc\Client\AuthenticationContract;
use Psr\Http\Message\RequestInterface;

/**
 * Bearer authenticates JSON-RPC request using a bearer token.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6750
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Bearer implements AuthenticationContract
{
    /**
     * @var string bearer token.
     */
    private $token;

    /**
     * Constructor.
     *
     * @param string $token bearer token.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(RequestInterface $request): RequestInterface
    {
        $header = 'Bearer ' . $this->token;

        return $request->withHeader('Authorization', $header);
    }
}