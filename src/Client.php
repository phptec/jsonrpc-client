<?php

namespace PhpTec\JsonRpc\Client;

use Psr\Http\Client\ClientInterface;

/**
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Client
{
    /**
     * @var \Psr\Http\Client\ClientInterface HTTP client instance.
     */
    private $_httpClient;

    public function invoke(string $name, array $args, ?string $id = null)
    {
        ;
    }

    public function setHttpClient(ClientInterface $httpClient): self
    {
        $this->_httpClient = $httpClient;

        return $this;
    }

    public function getHttpClient(): ClientInterface
    {
        if ($this->_httpClient === null) {
            $this->_httpClient = $this->defaultHttpClient();
        }

        return $this->_httpClient;
    }

    protected function defaultHttpClient(): ClientInterface
    {
        return new \GuzzleHttp\Client([]);
    }
}