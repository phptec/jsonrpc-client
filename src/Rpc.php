<?php

namespace PhpTec\JsonRpc\Client;

/**
 * Rpc is a DTO, which represents particular JSON-RPC invocation.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Rpc
{
    /**
     * @var string remote method (procedure) name.
     */
    private $method;

    /**
     * @var array<string|int, mixed> remote method (procedure) parameters (arguments).
     */
    private $params = [];

    /**
     * @var int|string|null request ID.
     */
    private $id;

    public function __construct(string $method = '', array $params = [], $id = null)
    {
        $this->setMethod($method)
            ->setParams($params)
            ->setId($id);
    }

    /**
     * @return string remote method (procedure) name.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method remote method (procedure) name.
     * @return static self reference.
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return array<string|int, mixed> remote method (procedure) parameters (arguments).
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<string|int, mixed> remote method (procedure) parameters (arguments).
     * @return static self reference.
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return int|string|null request ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string|null $id request ID.
     * @return static self reference.
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Creates new RPC instance.
     *
     * @param mixed ...$args constructor arguments.
     * @return static new RPC instance.
     */
    public static function new(...$args): self
    {
        return new static(...$args);
    }
}