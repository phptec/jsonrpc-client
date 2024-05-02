<?php

namespace PhpTec\JsonRpc\Client\Authentication;

use PhpTec\JsonRpc\Client\AuthenticationContract;
use Psr\Http\Message\RequestInterface;

/**
 * BasicAuth authenticates JSON-RPC request using Basic Auth.
 *
 * @see https://en.wikipedia.org/wiki/Basic_access_authentication
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class BasicAuth implements AuthenticationContract
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * Constructor.
     *
     * @param string $username username.
     * @param string $password password.
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(RequestInterface $request): RequestInterface
    {
        $header = 'Basic ' . base64_encode($this->username . ':' .  $this->password);

        return $request->withHeader('Authorization', $header);
    }
}