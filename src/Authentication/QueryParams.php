<?php

namespace PhpTec\JsonRpc\Client\Authentication;

use PhpTec\JsonRpc\Client\AuthenticationContract;
use Psr\Http\Message\RequestInterface;

/**
 * QueryParams authenticates JSON-RPC request by adding specified parameters to its query string.
 *
 * > Note: this authentication method is considered to be unsafe since it will cause credentials
 *   to appear at HTTP logs along with full URL. Use this method only, if there is no alternative.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class QueryParams implements AuthenticationContract
{
    /**
     * @var array query params, which should be added to the URI of each HTTP request.
     */
    private $params = [];

    /**
     * Constructor.
     *
     * @param array $params query params, which should be added to the URI of each HTTP request.
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(RequestInterface $request): RequestInterface
    {
        $uri = $request->getUri();
        $query = $uri->getQuery();
        $params = [];

        parse_str($query, $params);

        $params = array_merge($params, $this->params);

        $query = http_build_query($params, '', '&');

        $uri = $uri->withQuery($query);

        return $request->withUri($uri);
    }
}