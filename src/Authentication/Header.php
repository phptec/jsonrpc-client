<?php

namespace PhpTec\JsonRpc\Client\Authentication;

use PhpTec\JsonRpc\Client\AuthenticationContract;
use Psr\Http\Message\RequestInterface;

/**
 * Header authenticates JSON-RPC request adding specified custom header to it.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Header implements AuthenticationContract
{
    /**
     * @var string header name.
     */
    private $name;

    /**
     * @var string|string[] header value.
     */
    private $value;

    /**
     * Constructor.
     *
     * @param string $name header name.
     * @param string|string[] $value header value.
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader($this->name, $this->value);
    }
}