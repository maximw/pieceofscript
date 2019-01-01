<?php


namespace PieceofScript\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PieceofScript\Services\Config\Config;
use PieceofScript\Services\Contexts\ContextStack;
use PieceofScript\Services\Endpoints\Endpoint;
use PieceofScript\Services\Endpoints\EndpointCall;
use PieceofScript\Services\Errors\Endpoints\EndpointCallError;
use PieceofScript\Services\Errors\RuntimeError;
use PieceofScript\Services\Out\Out;
use PieceofScript\Services\Utils\Utils;
use PieceofScript\Services\Values\ArrayLiteral;
use PieceofScript\Services\Values\Hierarchy\BaseLiteral;
use PieceofScript\Services\Values\StringLiteral;

class HttpClient
{
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_PATH = 'path';
    const METHOD_DELETE = 'delete';
    const METHOD_HEAD = 'head';
    const METHOD_CONNECT = 'connect';
    const METHOD_OPTIONS = 'options';
    const METHOD_TRACE = 'trace';

    const METHODS = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_PATH,
        self::METHOD_DELETE,
        self::METHOD_HEAD,
        self::METHOD_CONNECT,
        self::METHOD_OPTIONS,
        self::METHOD_TRACE,
    ];

    const AUTH_BASIC = 'basic';
    const AUTH_DIGEST = 'digest';
    const AUTH_NTLM = 'ntlm';

    const AUTH_TYPES = [
        self::AUTH_BASIC,
        self::AUTH_DIGEST,
        self::AUTH_NTLM,
    ];

    /** @var EndpointCall */
    protected static $endpointCall;

    /** @var Endpoint */
    protected static $endpoint;

    /** @var ContextStack */
    protected static $contextStack;

    public static function doRequest(BaseLiteral $request, ContextStack $contextStack, EndpointCall $endpointCall): ArrayLiteral
    {
        static::$contextStack = $contextStack;
        static::$endpointCall = $endpointCall;
        static::$endpoint = $endpointCall->getEndpoint();

        list($requestParams, $options) = self::prepareOptions($request);

        $httpClient = new Client();

        $httpResponse = null;
        $start = microtime(true);
        try {
            $httpResponse = $httpClient->request(
                $requestParams['method'],
                $requestParams['url'],
                $options
            );
        } catch (RequestException $e) {
            // TODO output error $e->getMessage();
            if ($e->hasResponse()) {
                $httpResponse = $e->getResponse();
            }
        }
        $duration = microtime(true) - $start;

        if ($httpResponse instanceof Response) {
            $response = Utils::wrapValueContainer([
                'network' => true,
                'code' => $httpResponse->getStatusCode(),
                'status' => $httpResponse->getStatusCode() . ' ' . $httpResponse->getReasonPhrase(),
                'headers' => $httpResponse->getHeaders(),
                'cookies' => $requestParams['cookie']->toArray(),
                'raw' => (string)$httpResponse->getBody()->getContents(),
                'duration' => $duration,
            ]);
            $jsonBody = json_decode((string)$httpResponse->getBody(), true, Config::get()->getJsonMaxDepth());
            if (JSON_ERROR_NONE === json_last_error()) {
                $response['body'] = $jsonBody;
            } else {
                if (static::isJsonResponse($httpResponse)) {
                    Out::printWarning('Error parsing JSON. ' . json_last_error_msg());
                }
            }
        } else {
            $response = Utils::wrapValueContainer([
                'network' => false,
                'code' => null,
                'status' => null,
                'headers' => [],
                'cookies' => $requestParams['cookie']->toArray(),
                'duration' => $duration,
            ]);
        }

        return $response;
    }


    protected static function prepareOptions(BaseLiteral $request): array
    {
        if (!$request instanceof ArrayLiteral) {
            throw new EndpointCallError(static::$endpoint, '$request value has to be Array');
        }

        $requestParams['method'] = self::extractMethod($request);
        $requestParams['url'] = self::extractUrl($request);

        $headers = self::extractHeaders($request);
        $requestParams['cookie'] = self::extractCookies($request, $requestParams['url']);
        $auth = self::extractAuth($request);
        $query = self::extractQuery($request);
        $format = self::extractFormat($request);
        $data = self::extractData($request);

        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::COOKIES => $requestParams['cookie'],
            RequestOptions::AUTH => $auth,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::CONNECT_TIMEOUT => Config::get()->getHttpConnectTimeout(),
            RequestOptions::READ_TIMEOUT => Config::get()->getHttpReadTimeout(),
            RequestOptions::ALLOW_REDIRECTS => ['max' => Config::get()->getHttpMaxRedirects()],
        ];

        if (!empty($query)) {
            $options[RequestOptions::QUERY] = $query;
        }

        if ($format == Endpoint::FORMAT_JSON) {
            $options[RequestOptions::JSON] = $data;
        } elseif ($format == Endpoint::FORMAT_FROM) {
            if (!is_array($data)) {
                throw new EndpointCallError(self::$endpoint,'Form data has to be array');
            }
            $options[RequestOptions::FORM_PARAMS] = $data;
        } elseif ($format == Endpoint::FORMAT_MULTIPART) {
            if (!is_array($data)) {
                throw new EndpointCallError(self::$endpoint,'Form data has to be array');
            }
            $options[RequestOptions::MULTIPART] = self::prepareMultipartForm($data);
        }

        return [$requestParams, $options];
    }

    protected static function extractMethod(ArrayLiteral $request): string
    {
        if (!isset($request['method'])
            || !($request['method'] instanceof StringLiteral)
            || !in_array(strtolower($request['method']->getValue()), self::METHODS)) {
            throw new \Exception('Cannot call endpoint. Bad $request.method = "' . $request['method']->getValue() . '"');
        }
        return Utils::unwrapValueContainer($request['method']);
    }


    protected static function extractUrl(ArrayLiteral $request): string
    {
        if (!isset($request['url'])
            || !($request['url'] instanceof StringLiteral)) {
            throw new \Exception('Cannot call endpoint. $request.url is required');
        }
        return Utils::unwrapValueContainer($request['url']);
    }

    protected static function extractHeaders(ArrayLiteral $request): array
    {
        if (isset($request['headers'])
            && !($request['headers'] instanceof ArrayLiteral)) {
            throw new \Exception('Cannot call endpoint. $request.headers is invalid');
        }
        return Utils::unwrapValueContainer($request['headers'] ?? []);
    }

    protected static function extractCookies(ArrayLiteral $request, string $url): CookieJar
    {
        if (isset($request['cookies'])
            && !($request['cookies'] instanceof ArrayLiteral)) {
            throw new EndpointCallError(self::$endpoint, '$request.cookies is invalid');
        }
        $domain = parse_url($url, PHP_URL_HOST);
        $cookies = Utils::unwrapValueContainer($request['cookies'] ?? []);
        $cookieJar = CookieJar::fromArray($cookies, $domain);
        return $cookieJar;
    }

    protected static function extractAuth(ArrayLiteral $request)
    {
        if (isset($request['auth'])) {
            if (! $request['auth'] instanceof ArrayLiteral) {
                throw new EndpointCallError(self::$endpoint, '$request.auth is invalid');
            }
        }
        return Utils::unwrapValueContainer($request['auth'] ?? null);
    }

    protected static function extractQuery(ArrayLiteral $request)
    {
        if (isset($request['query'])
            && !($request['query'] instanceof ArrayLiteral || $request['query'] instanceof StringLiteral)) {
            throw new \Exception('Cannot call endpoint. $request.query is invalid');
        }
        return Utils::unwrapValueContainer($request['query'] ?? []);
    }

    protected static function extractFormat(ArrayLiteral $request): string
    {
        if (isset($request['format'])) {
            if (!($request['format'] instanceof StringLiteral) || !in_array($request['format']->getValue(), Endpoint::FORMATS)) {
                throw new \Exception('Cannot call endpoint. $request.format is invalid');
            }
        }
        return Utils::unwrapValueContainer($request['format'] ?? Endpoint::FORMAT_NONE);
    }

    protected static function extractData(ArrayLiteral $request)
    {
        return Utils::unwrapValueContainer($request['data'] ?? []);
    }

    protected static function prepareMultipartForm(array $data): array
    {
        $multipartItems = [];
        foreach ($data as $field => $value) {
            $multipartItem = [];
            if (empty($field) || !is_string($field)) {
                throw new \Exception('Multipart form item must have name');
            }
            if (empty($value['value']) && empty($value['file'])) {
                throw new \Exception('Multipart form item must have value');
            }
            if (!empty($value['value']) && !empty($value['file'])) {
                throw new \Exception('Multipart form item must have only one of fields "value" or "file"');
            }

            $multipartItem['name'] = $field;
            if (!empty($value['value'])) {
                $multipartItem['contents'] = (string) $value['value'];
            } else {
                if (!is_readable($value['file'])) {
                    throw new \Exception('Multipart form item file is not readable');
                }
                if (is_dir($value['file'])) {
                    throw new \Exception('Multipart form item given file is directory');
                }
                $multipartItem['contents'] = fopen($value['file'], 'r');
            }

            if (!empty($value['headers'])) {
                $headers = [];
                if (!is_array($value['headers'])) {
                    throw new \Exception('Multipart form item file  is directory');
                }
                foreach ($value['headers'] as $headerName => $headerValue) {
                    if (!is_string($headerName)) {
                        throw new \Exception('Multipart form item header name is not string');
                    }
                    if (!is_scalar($headerValue)) {
                        throw new \Exception('Multipart form item header value is not string');
                    }
                    $headers[$headerName] = $headerValue;
                }
                $multipartItem['headers'] = $headers;
            }

            if (!empty($value['filename'])) {
                $multipartItem['filename'] = (string) $value['filename'];
            }

            $multipartItems[] = $multipartItem;
        }

        return $multipartItems;
    }

    protected static function isJsonResponse(Response $response)
    {
        return strtolower($response->getContentType()) === 'application/json';
    }

}