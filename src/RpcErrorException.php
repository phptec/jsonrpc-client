<?php

namespace PhpTec\JsonRpc\Client;

/**
 * RpcErrorException represents an error returned from server, while processing particular PRC.
 *
 * This exception does not cover protocol or network errors.
 * Usually it indicates that parameters of the RPC were specified incorrectly.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class RpcErrorException extends \RuntimeException
{
    /**
     * @var mixed extra data associated with the error.
     */
    private $data;

    public function __construct(string $message, int $code = 0, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->data = $data;
    }

    /**
     * @return mixed extra data associated with the error.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Creates new instance from error JSON-RPC response.
     *
     * @param array $errorResponseData data from 'error' key in JSON-PRC response.
     * @return static new exception instance.
     */
    public static function fromErrorResponseData(array $errorResponseData): self
    {
        $message = 'Unknown error';
        if (isset($errorResponseData['message']) && is_string($errorResponseData['message'])) {
            $message = $errorResponseData['message'];
        }

        $code = 0;
        if (isset($errorResponseData['code']) && is_int($errorResponseData['code'])) {
            $code = $errorResponseData['code'];
        }

        $data = null;
        if (isset($errorResponseData['data'])) {
            $data = $errorResponseData['data'];
        }

        return new static($message, $code, $data);
    }
}