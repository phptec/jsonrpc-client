<?php

namespace PhpTec\JsonRpc\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Client
{
    /**
     * @var \Psr\Http\Client\ClientInterface HTTP client instance.
     */
    private $httpClient;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface HTTP request factory.
     */
    private $httpRequestFactory;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface HTTP message stream factory.
     */
    private $httpStreamFactory;

    /**
     * @var string JSON-RPC API endpoint URI.
     */
    private $endpointUri;

    /**
     * @var int JSON encode options (flags).
     * @see https://www.php.net/manual/en/function.json-encode
     */
    private $jsonEncodeOptions = 0;

    /**
     * Constructor.
     *
     * @param string $endpointUri JSON-RPC API endpoint URI.
     */
    public function __construct(string $endpointUri)
    {
        $this->endpointUri = $endpointUri;
    }

    /**
     * Invokes the remote method, returning its execution result.
     *
     * @param string $name remote method (procedure) name.
     * @param array<string, mixed> remote method (procedure) parameters (arguments).
     * @param int|string|null request ID.
     * @return mixed the invocation result.
     */
    public function invoke(string $name, array $params, $id = null)
    {
        $rpc = new Rpc($name, $params, $id);

        return $this->invokeRpc($rpc);
    }

    /**
     * Invokes the remote method, returning its execution result.
     *
     * @param \PhpTec\JsonRpc\Client\Rpc $rpc RPC DTO.
     * @return mixed the invocation result.
     */
    public function invokeRpc(Rpc $rpc)
    {
        $responseData = $this->sendHttpRequest($this->rpc2array($rpc));

        if (!empty($responseData['error']) || !array_key_exists('result', $responseData)) {
            throw RpcErrorException::fromErrorResponseData($responseData['error']);
        }

        return $responseData['result'];
    }

    /**
     * Invokes the batch of remote methods via single HTTP request.
     *
     * @param array<string, \PhpTec\JsonRpc\Client\Rpc|array<string, array>> $rpcs RPC specification indexed by request ID.
     * @return array<string, mixed> the invocation results indexed by request ID.
     */
    public function invokeBatch(array $rpcs): array
    {
        if (empty($rpcs)) {
            throw new \InvalidArgumentException('List of RPCs must not be empty.');
        }

        $requestData = [];
        foreach ($rpcs as $id => $rpc) {
            if ($rpc instanceof Rpc) {
                $rpcDto = $rpc;
            } elseif (is_array($rpc)) {
                if (count($rpc) !== 1) {
                    throw new \InvalidArgumentException('Each RPC can specify only single `[method => params]` set.');
                }
                foreach ($rpc as $method => $params) {
                    $rpcDto = new Rpc($method, $params);

                    break;
                }
            } else {
                throw new \InvalidArgumentException('An RPC can be specify either as array or `' . Rpc::class . '` instance.');
            }

            if ($rpcDto->getId() === null) {
                $rpcDto->setId($id);
            }

            $requestData[] = $this->rpc2array($rpcDto);
        }

        $batchResponseData = $this->sendHttpRequest($requestData);

        $results = [];
        foreach ($batchResponseData as $responseData) {
            if (!empty($responseData['error']) || !array_key_exists('result', $responseData)) {
                throw RpcErrorException::fromErrorResponseData($responseData['error']);
            }

            $results[$responseData['id']] = $responseData['result'];
        }

        return $results;
    }

    public function setHttpClient(ClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function getHttpClient(): ClientInterface
    {
        if ($this->httpClient === null) {
            $this->httpClient = $this->defaultHttpClient();
        }

        return $this->httpClient;
    }

    protected function defaultHttpClient(): ClientInterface
    {
        return new \GuzzleHttp\Client([]);
    }

    public function setHttpRequestFactory(RequestFactoryInterface $httpRequestFactory): self
    {
        $this->httpRequestFactory = $httpRequestFactory;

        return $this;
    }

    public function getHttpRequestFactory(): RequestFactoryInterface
    {
        if ($this->httpRequestFactory === null) {
            $this->httpRequestFactory = $this->defaultHttpRequestFactory();
        }

        return $this->httpRequestFactory;
    }

    protected function defaultHttpRequestFactory(): RequestFactoryInterface
    {
        return new \GuzzleHttp\Psr7\HttpFactory();
    }

    public function setHttpStreamFactory(StreamFactoryInterface $httpStreamFactory): self
    {
        $this->httpStreamFactory = $httpStreamFactory;

        return $this;
    }

    public function getHttpStreamFactory(): StreamFactoryInterface
    {
        if ($this->httpStreamFactory === null) {
            $this->httpStreamFactory = $this->defaultHttpStreamFactory();
        }

        return $this->httpStreamFactory;
    }

    protected function defaultHttpStreamFactory(): StreamFactoryInterface
    {
        return new \GuzzleHttp\Psr7\HttpFactory();
    }

    protected function sendHttpRequest(array $requestData): array
    {
        $json = $this->jsonEncode($requestData);
        $body = $this->getHttpStreamFactory()->createStream($json);

        $httpRequest = $this->getHttpRequestFactory()
            ->createRequest('POST', $this->endpointUri)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        $httpResponse = $this->getHttpClient()->sendRequest($httpRequest);

        $responseData = $this->jsonDecode($httpResponse->getBody()->__toString());

        return $responseData;
    }

    /**
     * @param \PhpTec\JsonRpc\Client\Rpc $rpc RPC DTO.
     * @return array RPC structured data.
     */
    protected function rpc2array(Rpc $rpc): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $rpc->getId() ?? 1,
            'method' => $rpc->getMethod(),
            'params' => $rpc->getParams(),
        ];
    }

    /**
     * @param mixed $data data to be encoded.
     * @return string JSON string.
     */
    protected function jsonEncode($data): string
    {
        return json_encode($data, $this->jsonEncodeOptions);
    }

    /**
     * @param string $json JSON string.
     * @return array decoded data.
     */
    protected function jsonDecode(string $json): array
    {
        $result = json_decode($json, true);

        $errorCode = json_last_error();
        if ($errorCode !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Unable to decode JSON: ' . json_last_error_msg(), $errorCode);
        }

        return $result;
    }

    /**
     * Calls the named method which is not a class method.
     * Do not call this method. This is a PHP magic method that we override to implement direct RPC invocation.
     *
     * @param string $method method name.
     * @param array $arguments method arguments.
     * @return mixed invocation result.
     */
    public function __call(string $method, array $arguments)
    {
        $params = [];
        $id = null;
        if (!empty($arguments)) {
            if (isset($arguments[0])) {
                // traditional arguments
                $params = $arguments[0];
                $id = $arguments[1] ?? null;
            } else {
                // named arguments, require PHP >= 8.0
                $params = $arguments;
            }
        }

        return $this->invoke($method, $params, $id);
    }
}