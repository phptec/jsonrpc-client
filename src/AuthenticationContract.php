<?php

namespace PhpTec\JsonRpc\Client;

use Psr\Http\Message\RequestInterface;

/**
 * AuthContract specifies the contract for JSON-RPC HTTP requests authentication.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface AuthenticationContract
{
    /**
     * Adds authentication to the given HTTP request.
     *
     * @param \Psr\Http\Message\RequestInterface $request raw HTTP request.
     * @return \Psr\Http\Message\RequestInterface authenticated HTTP request.
     */
    public function authenticate(RequestInterface $request): RequestInterface;
}