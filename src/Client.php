<?php

namespace PhpTec\JsonRpc\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Client represents a JSON-RPC client.
 *
 * Usage example:
 *
 * ```php
 * $jsonRpcClient = Client::new('https://example.test/json-rpc')
 *     ->setAuthentication(new BasicAuth('apiuser', 'secret'))
 *     ->setHttpClient(new \GuzzleHttp\Client([
 *         'timeout' => 15,
 *     ]));
 *
 * $result = $jsonRpcClient->invoke('subtract', [42, 23]);
 * $result = $jsonRpcClient->invoke('pow', [
 *     'number' => $number,
 *     'exponent' => $exponent,
 * ]);
 * ```
 *
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
     * @var \PhpTec\JsonRpc\Client\AuthenticationContract|null authentication for JSON-RPC request.
     */
    private $authentication;

    /**
     * @var \Psr\Log\LoggerInterface|null logger for JSON-RPC request debug (trace).
     * If not set - logging is disabled.
     */
    private $logger;

    /**
     * @var string JSON-RPC API endpoint URI.
     */
    private $endpointUri;

    /**
     * @var string|null value, which should be passed as HTTP header 'User-Agent' per each request.
     */
    private $userAgent;

    /**
     * @var string prefix, which should be automatically added for the method name in RPC request.
     */
    private $methodPrefix = '';

    /**
     * @var int JSON encode options (flags).
     * @see https://www.php.net/manual/en/function.json-encode
     */
    private $jsonEncodeOptions = 0;

    /**
     * @var int JSON decode options (flags).
     * @see https://www.php.net/manual/en/function.json-decode
     */
    private $jsonDecodeOptions = 0;

    /**
     * @var string name of the query string (GET) parameter, which should be used to duplicate RPC method name.
     *
     * @since 1.0.2
     */
    private $methodQueryParam = '';

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
     * @param array<string|int, mixed> remote method (procedure) parameters (arguments).
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

    /**
     * Sets up the HTTP client to be used for request sending.
     *
     * @param \Psr\Http\Client\ClientInterface $httpClient HTTP client to be used.
     * @return static self reference.
     */
    public function setHttpClient(ClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @return \Psr\Http\Client\ClientInterface HTTP client being used.
     */
    public function getHttpClient(): ClientInterface
    {
        if ($this->httpClient === null) {
            $this->httpClient = $this->defaultHttpClient();
        }

        return $this->httpClient;
    }

    /**
     * Creates default instance for the HTTP client.
     *
     * @return \Psr\Http\Client\ClientInterface HTTP client instance.
     */
    protected function defaultHttpClient(): ClientInterface
    {
        return new \GuzzleHttp\Client([]);
    }

    /**
     * @param \Psr\Http\Message\RequestFactoryInterface $httpRequestFactory HTTP request factory.
     * @return static self reference.
     */
    public function setHttpRequestFactory(RequestFactoryInterface $httpRequestFactory): self
    {
        $this->httpRequestFactory = $httpRequestFactory;

        return $this;
    }

    /**
     * @return \Psr\Http\Message\RequestFactoryInterface HTTP request factory.
     */
    public function getHttpRequestFactory(): RequestFactoryInterface
    {
        if ($this->httpRequestFactory === null) {
            $this->httpRequestFactory = $this->defaultHttpRequestFactory();
        }

        return $this->httpRequestFactory;
    }

    /**
     * Creates default instance for the HTTP request factory.
     *
     * @return \Psr\Http\Message\RequestFactoryInterface HTTP request factory.
     */
    protected function defaultHttpRequestFactory(): RequestFactoryInterface
    {
        return new \GuzzleHttp\Psr7\HttpFactory();
    }

    /**
     * @param \Psr\Http\Message\StreamFactoryInterface $httpStreamFactory HTTP stream factory.
     * @return static self reference.
     */
    public function setHttpStreamFactory(StreamFactoryInterface $httpStreamFactory): self
    {
        $this->httpStreamFactory = $httpStreamFactory;

        return $this;
    }

    /**
     * @return \Psr\Http\Message\StreamFactoryInterface HTTP stream factory.
     */
    public function getHttpStreamFactory(): StreamFactoryInterface
    {
        if ($this->httpStreamFactory === null) {
            $this->httpStreamFactory = $this->defaultHttpStreamFactory();
        }

        return $this->httpStreamFactory;
    }

    /**
     * Creates default instance for the HTTP stream factory.
     *
     * @return \Psr\Http\Message\StreamFactoryInterface HTTP stream factory.
     */
    protected function defaultHttpStreamFactory(): StreamFactoryInterface
    {
        return new \GuzzleHttp\Psr7\HttpFactory();
    }

    /**
     * Sets request authentication strategy.
     *
     * @param \PhpTec\JsonRpc\Client\AuthenticationContract|null $authentication request authentication strategy.
     * @return static self reference.
     */
    public function setAuthentication(?AuthenticationContract $authentication): self
    {
        $this->authentication = $authentication;

        return $this;
    }

    /**
     * Returns currently used request authentication strategy.
     *
     * @return \PhpTec\JsonRpc\Client\AuthenticationContract|null request authentication strategy.
     */
    public function getAuthentication(): ?AuthenticationContract
    {
        return $this->authentication;
    }

    /**
     * Sets the logger for the JSON-RPC request debug (trace).
     *
     * @param \Psr\Log\LoggerInterface|null $logger logger instance, if `null` given - logging will be disabled.
     * @return static self reference.
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Returns the logger for JSON-RPC request debug (trace).
     *
     * @return \Psr\Log\LoggerInterface|null logger instance, `null` means logging is disabled.
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Sets the JSON-RPC API endpoint URI.
     * For example: 'https://example.test/json-rpc'
     *
     * @param string $endpointUri JSON-RPC API endpoint URI.
     * @return static self reference.
     */
    public function setEndpointUri(string $endpointUri): self
    {
        $this->endpointUri = $endpointUri;

        return $this;
    }

    /**
     * @return string JSON-RPC API endpoint URI.
     */
    public function getEndpointUri(): string
    {
        return $this->endpointUri;
    }

    /**
     * Sets the value, which should be passed as HTTP header 'User-Agent' per each request.
     *
     * > Note: particular HTTP client may set its own value of 'User-Agent' regardless of this option.
     *
     * @param string|null $userAgent HTTP user agent, `null` means set no header.
     * @return static self reference.
     */
    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Returns HTTP user agent.
     *
     * @return string|null HTTP user agent.
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Sets the prefix, which should be automatically added for the method name in RPC request.
     *
     * @param string $prefix method name prefix.
     * @return static self reference.
     */
    public function setMethodPrefix(string $prefix): self
    {
        $this->methodPrefix = $prefix;

        return $this;
    }

    /**
     * Returns the prefix, which should be automatically added for the method name in RPC request.
     *
     * @return string method name prefix.
     */
    public function getMethodPrefix(): string
    {
        return $this->methodPrefix;
    }

    /**
     * Sets JSON encode options (flags).
     * @see https://www.php.net/manual/en/function.json-encode
     *
     * @param int $jsonEncodeOptions JSON encode options (flags).
     * @return static self reference.
     */
    public function setJsonEncodeOptions(int $jsonEncodeOptions): self
    {
        $this->jsonEncodeOptions = $jsonEncodeOptions;

        return $this;
    }

    /**
     * Returns JSON encode options (flags).
     * @see https://www.php.net/manual/en/function.json-encode
     *
     * @return int JSON encode options (flags).
     */
    public function getJsonEncodeOptions(): int
    {
        return $this->jsonEncodeOptions;
    }

    /**
     * Sets JSON decode options (flags).
     * @see https://www.php.net/manual/en/function.json-decode
     *
     * @param int $jsonDecodeOptions JSON decode options (flags).
     * @return static self reference.
     */
    public function setJsonDecodeOptions(int $jsonDecodeOptions): self
    {
        $this->jsonDecodeOptions = $jsonDecodeOptions;

        return $this;
    }

    /**
     * Returns JSON decode options (flags).
     * @see https://www.php.net/manual/en/function.json-decode
     *
     * @return int JSON decode options (flags).
     */
    public function getJsonDecodeOptions(): int
    {
        return $this->jsonDecodeOptions;
    }

    /**
     * Sets the name of the query string (GET) parameter, which should be used to duplicate RPC method name
     * in the outgoing HTTP request.
     *
     * With such parameter set the actual request URI will look like:
     *
     * ```
     * https://example.test/json-rpc?rpc=rpc_method_name
     * ```
     *
     * This could be useful for debug purposes, allowing RPC method name to appear at web server access logs.
     *
     * @since 1.0.2
     *
     * @param string $methodQueryParam query param name.
     * @return static self reference.
     */
    public function setMethodQueryParam(string $methodQueryParam): self
    {
        $this->methodQueryParam = $methodQueryParam;

        return $this;
    }

    /**
     * Returns the name of the query string (GET) parameter, which should be used to duplicate RPC method name
     * in the outgoing HTTP request.
     *
     * @since 1.0.2
     *
     * @return string query param name.
     */
    public function getMethodQueryParam(): string
    {
        return $this->methodQueryParam;
    }

    /**
     * Creates new HTTP request with given data.
     *
     * @param array $requestData request data.
     * @return \Psr\Http\Message\RequestInterface HTTP request instance.
     */
    protected function createHttpRequest(array $requestData): RequestInterface
    {
        $json = $this->jsonEncode($requestData);
        $body = $this->getHttpStreamFactory()->createStream($json);

        $httpRequest = $this->getHttpRequestFactory()
            ->createRequest('POST', $this->endpointUri)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        if (($userAgent = $this->getUserAgent()) !== null) {
            $httpRequest = $httpRequest->withHeader('User-Agent', $userAgent);
        }

        if (($authentication = $this->getAuthentication()) !== null) {
            $httpRequest = $authentication->authenticate($httpRequest);
        }

        return $httpRequest;
    }

    /**
     * Sends the HTTP request with given data.
     *
     * @param array $requestData request data.
     * @return array response decoded data.
     * @throws \Psr\Http\Client\ClientExceptionInterface HTTP transfer exception.
     */
    protected function sendHttpRequest(array $requestData): array
    {
        $httpRequest = $this->createHttpRequest($requestData);

        $methodQueryParam = $this->getMethodQueryParam();
        if (!empty($methodQueryParam)) {
            $httpRequest = $this->addRequestQueryParams(
                $httpRequest,
                [
                    $methodQueryParam => $this->extractMethodNames($requestData),
                ]
            );
        }

        try {
            $beginTime = microtime(true);

            $httpResponse = $this->getHttpClient()->sendRequest($httpRequest);

            $endTime = microtime(true);

            $statusCode = $httpResponse->getStatusCode();
            if ($statusCode < 200 || $statusCode > 299) {
                $errorBody = strip_tags($httpResponse->getBody()->getContents());
                $bodyLimit = 255;
                if (strlen($errorBody) > $bodyLimit) {
                    $errorBody = substr($errorBody, 0, $bodyLimit) . '...';
                }

                throw new \RuntimeException("{$statusCode} {$httpResponse->getReasonPhrase()}: {$errorBody}", $statusCode);
            }

            $responseData = $this->jsonDecode($httpResponse->getBody()->__toString());
        } catch (\Exception $exception) {
            if (($logger = $this->getLogger()) !== null) {
                $logger->error($exception->getMessage(), [
                    'exception' => $exception,
                    'uri' => $this->endpointUri,
                    'request' => $requestData,
                ]);
            }

            throw $exception;
        }

        if (($logger = $this->getLogger()) !== null) {
            $logger->debug('JSON-RPC Client Request', [
                'uri' => $this->endpointUri,
                'request' => $requestData,
                'response' => $responseData,
                'time' => $endTime - $beginTime,
            ]);
        }

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
            'method' => $this->getMethodPrefix() . $rpc->getMethod(),
            'params' => $rpc->getParams(),
        ];
    }

    /**
     * @param mixed $data data to be encoded.
     * @return string JSON string.
     */
    protected function jsonEncode($data): string
    {
        return json_encode($data, $this->getJsonEncodeOptions());
    }

    /**
     * @param string $json JSON string.
     * @return array decoded data.
     */
    protected function jsonDecode(string $json): array
    {
        $result = json_decode($json, true, 512, $this->getJsonDecodeOptions());

        $errorCode = json_last_error();
        if ($errorCode !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Unable to decode JSON: ' . json_last_error_msg(), $errorCode);
        }

        return $result;
    }

    /**
     * @param array $requestData raw RPC request data.
     * @return string[]|string RPC method name or array of names.
     */
    private function extractMethodNames(array $requestData)
    {
        if (isset($requestData['method'])) {
            return $requestData['method'];
        }

        return array_column($requestData, 'method', 'id');
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request raw HTTP request.
     * @param array $queryParams query params, which should be added to request.
     * @return \Psr\Http\Message\RequestInterface adjusted HTTP request.
     */
    private function addRequestQueryParams(RequestInterface $request, array $queryParams): RequestInterface
    {
        if (empty($queryParams)) {
            return $request;
        }

        $uri = $request->getUri();
        $query = $uri->getQuery();
        $params = [];

        parse_str($query, $params);

        $params = array_merge($params, $queryParams);

        $query = http_build_query($params, '', '&');

        $uri = $uri->withQuery($query);

        return $request->withUri($uri);
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
        if (!empty($arguments)) {
            if (isset($arguments[0])) {
                // traditional arguments
                if (count($arguments) > 1 || !is_array($arguments[0])) {
                    $params = $arguments;
                } else {
                    $params = $arguments[0];
                }
            } else {
                // named arguments, require PHP >= 8.0
                $params = $arguments;
            }
        }

        return $this->invoke($method, $params);
    }

    /**
     * Clones this object.
     * This method can be useful in creation of the invocation chain, providing syntax sugar.
     *
     * @return static cloned instance of self.
     */
    public function clone(): self
    {
        return clone $this;
    }

    /**
     * Creates new JSON-RPC Client instance.
     *
     * @param mixed ...$args constructor arguments.
     * @return static new instance.
     */
    public static function new(...$args): self
    {
        return new static(...$args);
    }
}