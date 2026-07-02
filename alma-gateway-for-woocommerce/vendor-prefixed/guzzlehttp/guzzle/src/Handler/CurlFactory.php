<?php

namespace Alma\Vendor\GuzzleHttp\Handler;

use Alma\Vendor\GuzzleHttp\Exception\ConnectException;
use Alma\Vendor\GuzzleHttp\Exception\RequestException;
use Alma\Vendor\GuzzleHttp\Promise as P;
use Alma\Vendor\GuzzleHttp\Promise\FulfilledPromise;
use Alma\Vendor\GuzzleHttp\Promise\PromiseInterface;
use Alma\Vendor\GuzzleHttp\Psr7\LazyOpenStream;
use Alma\Vendor\GuzzleHttp\TransferStats;
use Alma\Vendor\GuzzleHttp\Utils;
use Alma\Vendor\Psr\Http\Message\RequestInterface;
use Alma\Vendor\Psr\Http\Message\UriInterface;

/**
 * Creates curl resources from a request
 *
 * @final
 */
class CurlFactory implements CurlFactoryInterface
{
    public const CURL_VERSION_STR = 'curl_version';

    /**
     * @deprecated
     */
    public const LOW_CURL_VERSION_NUMBER = '7.21.2';

    /**
     * @var resource[]|\Alma_Gateway_CurlHandle[]
     */
    private $handles = [];

    /**
     * @var int Total number of idle handles to keep in cache
     */
    private $maxHandles;

    /**
     * @param int $maxHandles Maximum number of idle handles.
     */
    public function __construct(int $maxHandles)
    {
        $this->maxHandles = $maxHandles;
    }

    public function create(RequestInterface $request, array $options): EasyHandle
    {
        $protocolVersion = $request->getProtocolVersion();

        if ('2' === $protocolVersion || '2.0' === $protocolVersion) {
            if (!self::supportsHttp2()) {
                throw new ConnectException('HTTP/2 is supported by the cURL handler, however libcurl is built without HTTP/2 support.', $request);
            }
        } elseif ('1.0' !== $protocolVersion && '1.1' !== $protocolVersion) {
            throw new ConnectException(sprintf('HTTP/%s is not supported by the cURL handler.', $protocolVersion), $request);
        }

        if (isset($options['curl']['body_as_string'])) {
            $options['_body_as_string'] = $options['curl']['body_as_string'];
            unset($options['curl']['body_as_string']);
        }

        $easy = new EasyHandle();
        $easy->request = $request;
        $easy->options = $options;
        $conf = $this->getDefaultConf($easy);
        $this->applyMethod($easy, $conf);
        $this->applyHandlerOptions($easy, $conf);
        $this->applyHeaders($easy, $conf);
        unset($conf['_headers']);

        // Add handler options from the request configuration options
        if (isset($options['curl'])) {
            $conf = \array_replace($conf, $options['curl']);
        }

        $conf[\Alma_Gateway_CURLOPT_HEADERFUNCTION] = $this->createHeaderFn($easy);
        $easy->handle = $this->handles ? \array_pop($this->handles) : \curl_init();
        curl_setopt_array($easy->handle, $conf);

        return $easy;
    }

    private static function supportsHttp2(): bool
    {
        static $supportsHttp2 = null;

        if (null === $supportsHttp2) {
            $supportsHttp2 = self::supportsTls12()
                && defined('CURL_VERSION_HTTP2')
                && (\Alma_Gateway_CURL_VERSION_HTTP2 & \curl_version()['features']);
        }

        return $supportsHttp2;
    }

    private static function supportsTls12(): bool
    {
        static $supportsTls12 = null;

        if (null === $supportsTls12) {
            $supportsTls12 = \Alma_Gateway_CURL_SSLVERSION_TLSv1_2 & \curl_version()['features'];
        }

        return $supportsTls12;
    }

    private static function supportsTls13(): bool
    {
        static $supportsTls13 = null;

        if (null === $supportsTls13) {
            $supportsTls13 = defined('CURL_SSLVERSION_TLSv1_3')
                && (\Alma_Gateway_CURL_SSLVERSION_TLSv1_3 & \curl_version()['features']);
        }

        return $supportsTls13;
    }

    public function release(EasyHandle $easy): void
    {
        $resource = $easy->handle;
        unset($easy->handle);

        if (\count($this->handles) >= $this->maxHandles) {
            if (PHP_VERSION_ID < 80000) {
                \curl_close($resource);
            }
        } else {
            // Remove all callback functions as they can hold onto references
            // and are not cleaned up by curl_reset. Using curl_setopt_array
            // does not work for some reason, so removing each one
            // individually.
            \curl_setopt($resource, \Alma_Gateway_CURLOPT_HEADERFUNCTION, null);
            \curl_setopt($resource, \Alma_Gateway_CURLOPT_READFUNCTION, null);
            \curl_setopt($resource, \Alma_Gateway_CURLOPT_WRITEFUNCTION, null);
            \curl_setopt($resource, \Alma_Gateway_CURLOPT_PROGRESSFUNCTION, null);
            \curl_reset($resource);
            $this->handles[] = $resource;
        }
    }

    /**
     * Completes a cURL transaction, either returning a response promise or a
     * rejected promise.
     *
     * @param callable(RequestInterface, array): PromiseInterface $handler
     * @param CurlFactoryInterface                                $factory Dictates how the handle is released
     */
    public static function finish(callable $handler, EasyHandle $easy, CurlFactoryInterface $factory): PromiseInterface
    {
        if (isset($easy->options['on_stats'])) {
            self::invokeStats($easy);
        }

        if (!$easy->response || $easy->errno) {
            return self::finishError($handler, $easy, $factory);
        }

        // Return the response if it is present and there is no error.
        $factory->release($easy);

        // Rewind the body of the response if possible.
        $body = $easy->response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return new FulfilledPromise($easy->response);
    }

    private static function invokeStats(EasyHandle $easy): void
    {
        $curlStats = \curl_getinfo($easy->handle);
        $curlStats['appconnect_time'] = \curl_getinfo($easy->handle, \Alma_Gateway_CURLINFO_APPCONNECT_TIME);
        $stats = new TransferStats(
            $easy->request,
            $easy->response,
            $curlStats['total_time'],
            $easy->errno,
            $curlStats
        );
        ($easy->options['on_stats'])($stats);
    }

    /**
     * @param callable(RequestInterface, array): PromiseInterface $handler
     */
    private static function finishError(callable $handler, EasyHandle $easy, CurlFactoryInterface $factory): PromiseInterface
    {
        // Get error information and release the handle to the factory.
        $ctx = [
            'errno' => $easy->errno,
            'error' => \curl_error($easy->handle),
            'appconnect_time' => \curl_getinfo($easy->handle, \Alma_Gateway_CURLINFO_APPCONNECT_TIME),
        ] + \curl_getinfo($easy->handle);
        $ctx[self::CURL_VERSION_STR] = self::getCurlVersion();
        $factory->release($easy);

        // Retry when nothing is present or when curl failed to rewind.
        if (empty($easy->options['_err_message']) && (!$easy->errno || $easy->errno == 65)) {
            return self::retryFailedRewind($handler, $easy, $ctx);
        }

        return self::createRejection($easy, $ctx);
    }

    private static function getCurlVersion(): string
    {
        static $curlVersion = null;

        if (null === $curlVersion) {
            $curlVersion = \curl_version()['version'];
        }

        return $curlVersion;
    }

    private static function createRejection(EasyHandle $easy, array $ctx): PromiseInterface
    {
        static $connectionErrors = [
            \Alma_Gateway_CURLE_OPERATION_TIMEOUTED => true,
            \Alma_Gateway_CURLE_COULDNT_RESOLVE_HOST => true,
            \Alma_Gateway_CURLE_COULDNT_CONNECT => true,
            \Alma_Gateway_CURLE_SSL_CONNECT_ERROR => true,
            \Alma_Gateway_CURLE_GOT_NOTHING => true,
        ];

        if ($easy->createResponseException) {
            return P\Create::rejectionFor(
                new RequestException(
                    'An error was encountered while creating the response',
                    $easy->request,
                    $easy->response,
                    $easy->createResponseException,
                    $ctx
                )
            );
        }

        // If an exception was encountered during the onHeaders event, then
        // return a rejected promise that wraps that exception.
        if ($easy->onHeadersException) {
            return P\Create::rejectionFor(
                new RequestException(
                    'An error was encountered during the on_headers event',
                    $easy->request,
                    $easy->response,
                    $easy->onHeadersException,
                    $ctx
                )
            );
        }

        $uri = $easy->request->getUri();

        $sanitizedError = self::sanitizeCurlError($ctx['error'] ?? '', $uri);

        $message = \sprintf(
            'cURL error %s: %s (%s)',
            $ctx['errno'],
            $sanitizedError,
            'see https://curl.haxx.se/libcurl/c/libcurl-errors.html'
        );

        if ('' !== $sanitizedError) {
            $redactedUriString = \Alma\Vendor\GuzzleHttp\Psr7\Utils::redactUserInfo($uri)->__toString();
            if ($redactedUriString !== '' && false === \strpos($sanitizedError, $redactedUriString)) {
                $message .= \sprintf(' for %s', $redactedUriString);
            }
        }

        // Create a connection exception if it was a specific error code.
        $error = isset($connectionErrors[$easy->errno])
            ? new ConnectException($message, $easy->request, null, $ctx)
            : new RequestException($message, $easy->request, $easy->response, null, $ctx);

        return P\Create::rejectionFor($error);
    }

    private static function sanitizeCurlError(string $error, UriInterface $uri): string
    {
        if ('' === $error) {
            return $error;
        }

        $baseUri = $uri->withQuery('')->withFragment('');
        $baseUriString = $baseUri->__toString();

        if ('' === $baseUriString) {
            return $error;
        }

        $redactedUriString = \Alma\Vendor\GuzzleHttp\Psr7\Utils::redactUserInfo($baseUri)->__toString();

        return str_replace($baseUriString, $redactedUriString, $error);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getDefaultConf(EasyHandle $easy): array
    {
        $conf = [
            '_headers' => $easy->request->getHeaders(),
            \Alma_Gateway_CURLOPT_CUSTOMREQUEST => $easy->request->getMethod(),
            \Alma_Gateway_CURLOPT_URL => (string) $easy->request->getUri()->withFragment(''),
            \Alma_Gateway_CURLOPT_RETURNTRANSFER => false,
            \Alma_Gateway_CURLOPT_HEADER => false,
            \Alma_Gateway_CURLOPT_CONNECTTIMEOUT => 300,
        ];

        if (\defined('CURLOPT_PROTOCOLS')) {
            $conf[\Alma_Gateway_CURLOPT_PROTOCOLS] = \Alma_Gateway_CURLPROTO_HTTP | \Alma_Gateway_CURLPROTO_HTTPS;
        }

        $version = $easy->request->getProtocolVersion();

        if ('2' === $version || '2.0' === $version) {
            $conf[\Alma_Gateway_CURLOPT_HTTP_VERSION] = \Alma_Gateway_CURL_HTTP_VERSION_2_0;
        } elseif ('1.1' === $version) {
            $conf[\Alma_Gateway_CURLOPT_HTTP_VERSION] = \Alma_Gateway_CURL_HTTP_VERSION_1_1;
        } else {
            $conf[\Alma_Gateway_CURLOPT_HTTP_VERSION] = \Alma_Gateway_CURL_HTTP_VERSION_1_0;
        }

        return $conf;
    }

    private function applyMethod(EasyHandle $easy, array &$conf): void
    {
        $body = $easy->request->getBody();
        $size = $body->getSize();

        if ($size === null || $size > 0) {
            $this->applyBody($easy->request, $easy->options, $conf);

            return;
        }

        $method = $easy->request->getMethod();
        if ($method === 'PUT' || $method === 'POST') {
            // See https://datatracker.ietf.org/doc/html/rfc7230#section-3.3.2
            if (!$easy->request->hasHeader('Content-Length')) {
                $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = 'Content-Length: 0';
            }
        } elseif ($method === 'HEAD') {
            $conf[\Alma_Gateway_CURLOPT_NOBODY] = true;
            unset(
                $conf[\Alma_Gateway_CURLOPT_WRITEFUNCTION],
                $conf[\Alma_Gateway_CURLOPT_READFUNCTION],
                $conf[\Alma_Gateway_CURLOPT_FILE],
                $conf[\Alma_Gateway_CURLOPT_INFILE]
            );
        }
    }

    private function applyBody(RequestInterface $request, array $options, array &$conf): void
    {
        $size = $request->hasHeader('Content-Length')
            ? (int) $request->getHeaderLine('Content-Length')
            : null;

        // Send the body as a string if the size is less than 1MB OR if the
        // [curl][body_as_string] request value is set.
        if (($size !== null && $size < 1000000) || !empty($options['_body_as_string'])) {
            $conf[\Alma_Gateway_CURLOPT_POSTFIELDS] = (string) $request->getBody();
            // Don't duplicate the Content-Length header
            $this->removeHeader('Content-Length', $conf);
            $this->removeHeader('Transfer-Encoding', $conf);
        } else {
            $conf[\Alma_Gateway_CURLOPT_UPLOAD] = true;
            if ($size !== null) {
                $conf[\Alma_Gateway_CURLOPT_INFILESIZE] = $size;
                $this->removeHeader('Content-Length', $conf);
            }
            $body = $request->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $conf[\Alma_Gateway_CURLOPT_READFUNCTION] = static function ($ch, $fd, $length) use ($body) {
                return $body->read($length);
            };
        }

        // If the Expect header is not present, prevent curl from adding it
        if (!$request->hasHeader('Expect')) {
            $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = 'Expect:';
        }

        // cURL sometimes adds a content-type by default. Prevent this.
        if (!$request->hasHeader('Content-Type')) {
            $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = 'Content-Type:';
        }
    }

    private function applyHeaders(EasyHandle $easy, array &$conf): void
    {
        foreach ($conf['_headers'] as $name => $values) {
            foreach ($values as $value) {
                $value = (string) $value;
                if ($value === '') {
                    // cURL requires a special format for empty headers.
                    // See https://github.com/guzzle/guzzle/issues/1882 for more details.
                    $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = "$name;";
                } else {
                    $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = "$name: $value";
                }
            }
        }

        // Remove the Accept header if one was not set
        if (!$easy->request->hasHeader('Accept')) {
            $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = 'Accept:';
        }
    }

    /**
     * Remove a header from the options array.
     *
     * @param string $name    Case-insensitive header to remove
     * @param array  $options Array of options to modify
     */
    private function removeHeader(string $name, array &$options): void
    {
        foreach (\array_keys($options['_headers']) as $key) {
            if (!\strcasecmp($key, $name)) {
                unset($options['_headers'][$key]);

                return;
            }
        }
    }

    private function applyHandlerOptions(EasyHandle $easy, array &$conf): void
    {
        $options = $easy->options;
        if (isset($options['verify'])) {
            if ($options['verify'] === false) {
                unset($conf[\Alma_Gateway_CURLOPT_CAINFO]);
                $conf[\Alma_Gateway_CURLOPT_SSL_VERIFYHOST] = 0;
                $conf[\Alma_Gateway_CURLOPT_SSL_VERIFYPEER] = false;
            } else {
                $conf[\Alma_Gateway_CURLOPT_SSL_VERIFYHOST] = 2;
                $conf[\Alma_Gateway_CURLOPT_SSL_VERIFYPEER] = true;
                if (\is_string($options['verify'])) {
                    // Throw an error if the file/folder/link path is not valid or doesn't exist.
                    if (!\file_exists($options['verify'])) {
                        throw new \InvalidArgumentException("SSL CA bundle not found: {$options['verify']}");
                    }
                    // If it's a directory or a link to a directory use CURLOPT_CAPATH.
                    // If not, it's probably a file, or a link to a file, so use CURLOPT_CAINFO.
                    if (
                        \is_dir($options['verify'])
                        || (
                            \is_link($options['verify']) === true
                            && ($verifyLink = \readlink($options['verify'])) !== false
                            && \is_dir($verifyLink)
                        )
                    ) {
                        $conf[\Alma_Gateway_CURLOPT_CAPATH] = $options['verify'];
                    } else {
                        $conf[\Alma_Gateway_CURLOPT_CAINFO] = $options['verify'];
                    }
                }
            }
        }

        if (!isset($options['curl'][\Alma_Gateway_CURLOPT_ENCODING]) && !empty($options['decode_content'])) {
            $accept = $easy->request->getHeaderLine('Accept-Encoding');
            if ($accept) {
                $conf[\Alma_Gateway_CURLOPT_ENCODING] = $accept;
            } else {
                // The empty string enables all available decoders and implicitly
                // sets a matching 'Accept-Encoding' header.
                $conf[\Alma_Gateway_CURLOPT_ENCODING] = '';
                // But as the user did not specify any encoding preference,
                // let's leave it up to server by preventing curl from sending
                // the header, which will be interpreted as 'Accept-Encoding: *'.
                // https://www.rfc-editor.org/rfc/rfc9110#field.accept-encoding
                $conf[\Alma_Gateway_CURLOPT_HTTPHEADER][] = 'Accept-Encoding:';
            }
        }

        if (!isset($options['sink'])) {
            // Use a default temp stream if no sink was set.
            $options['sink'] = \Alma\Vendor\GuzzleHttp\Psr7\Utils::tryFopen('php://temp', 'w+');
        }
        $sink = $options['sink'];
        if (!\is_string($sink)) {
            $sink = \Alma\Vendor\GuzzleHttp\Psr7\Utils::streamFor($sink);
        } elseif (!\is_dir(\dirname($sink))) {
            // Ensure that the directory exists before failing in curl.
            throw new \RuntimeException(\sprintf('Directory %s does not exist for sink value of %s', \dirname($sink), $sink));
        } else {
            $sink = new LazyOpenStream($sink, 'w+');
        }
        $easy->sink = $sink;
        $conf[\Alma_Gateway_CURLOPT_WRITEFUNCTION] = static function ($ch, $write) use ($sink): int {
            return $sink->write($write);
        };

        $timeoutRequiresNoSignal = false;
        if (isset($options['timeout'])) {
            $timeoutRequiresNoSignal |= $options['timeout'] < 1;
            $conf[\Alma_Gateway_CURLOPT_TIMEOUT_MS] = $options['timeout'] * 1000;
        }

        // CURL default value is CURL_IPRESOLVE_WHATEVER
        if (isset($options['force_ip_resolve'])) {
            if ('v4' === $options['force_ip_resolve']) {
                $conf[\Alma_Gateway_CURLOPT_IPRESOLVE] = \Alma_Gateway_CURL_IPRESOLVE_V4;
            } elseif ('v6' === $options['force_ip_resolve']) {
                $conf[\Alma_Gateway_CURLOPT_IPRESOLVE] = \Alma_Gateway_CURL_IPRESOLVE_V6;
            }
        }

        if (isset($options['connect_timeout'])) {
            $timeoutRequiresNoSignal |= $options['connect_timeout'] < 1;
            $conf[\Alma_Gateway_CURLOPT_CONNECTTIMEOUT_MS] = $options['connect_timeout'] * 1000;
        }

        if ($timeoutRequiresNoSignal && \strtoupper(\substr(\PHP_OS, 0, 3)) !== 'WIN') {
            $conf[\Alma_Gateway_CURLOPT_NOSIGNAL] = true;
        }

        if (isset($options['proxy'])) {
            if (!\is_array($options['proxy'])) {
                $conf[\Alma_Gateway_CURLOPT_PROXY] = $options['proxy'];
            } else {
                $scheme = $easy->request->getUri()->getScheme();
                if (isset($options['proxy'][$scheme])) {
                    $host = $easy->request->getUri()->getHost();
                    if (isset($options['proxy']['no']) && Utils::isHostInNoProxy($host, $options['proxy']['no'])) {
                        unset($conf[\Alma_Gateway_CURLOPT_PROXY]);
                    } else {
                        $conf[\Alma_Gateway_CURLOPT_PROXY] = $options['proxy'][$scheme];
                    }
                }
            }
        }

        if (isset($options['crypto_method'])) {
            $protocolVersion = $easy->request->getProtocolVersion();

            // If HTTP/2, upgrade TLS 1.0 and 1.1 to 1.2
            if ('2' === $protocolVersion || '2.0' === $protocolVersion) {
                if (
                    \STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT === $options['crypto_method']
                    || \STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT === $options['crypto_method']
                    || \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT === $options['crypto_method']
                ) {
                    $conf[\Alma_Gateway_CURLOPT_SSLVERSION] = \Alma_Gateway_CURL_SSLVERSION_TLSv1_2;
                } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') && \STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT === $options['crypto_method']) {
                    if (!self::supportsTls13()) {
                        throw new \InvalidArgumentException('Invalid crypto_method request option: TLS 1.3 not supported by your version of cURL');
                    }
                    $conf[\Alma_Gateway_CURLOPT_SSLVERSION] = \Alma_Gateway_CURL_SSLVERSION_TLSv1_3;
                } else {
                    throw new \InvalidArgumentException('Invalid crypto_method request option: unknown version provided');
                }
            } elseif (\STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT === $options['crypto_method']) {
                $conf[\Alma_Gateway_CURLOPT_SSLVERSION] = \Alma_Gateway_CURL_SSLVERSION_TLSv1_0;
            } elseif (\STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT === $options['crypto_method']) {
                $conf[\Alma_Gateway_CURLOPT_SSLVERSION] = \Alma_Gateway_CURL_SSLVERSION_TLSv1_1;
            } elseif (\STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT === $options['crypto_method']) {
                if (!self::supportsTls12()) {
                    throw new \InvalidArgumentException('Invalid crypto_method request option: TLS 1.2 not supported by your version of cURL');
                }
                $conf[\Alma_Gateway_CURLOPT_SSLVERSION] = \Alma_Gateway_CURL_SSLVERSION_TLSv1_2;
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') && \STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT === $options['crypto_method']) {
                if (!self::supportsTls13()) {
                    throw new \InvalidArgumentException('Invalid crypto_method request option: TLS 1.3 not supported by your version of cURL');
                }
                $conf[\Alma_Gateway_CURLOPT_SSLVERSION] = \Alma_Gateway_CURL_SSLVERSION_TLSv1_3;
            } else {
                throw new \InvalidArgumentException('Invalid crypto_method request option: unknown version provided');
            }
        }

        if (isset($options['cert'])) {
            $cert = $options['cert'];
            if (\is_array($cert)) {
                $conf[\Alma_Gateway_CURLOPT_SSLCERTPASSWD] = $cert[1];
                $cert = $cert[0];
            }
            if (!\file_exists($cert)) {
                throw new \InvalidArgumentException("SSL certificate not found: {$cert}");
            }
            // OpenSSL (versions 0.9.3 and later) also support "P12" for PKCS#12-encoded files.
            // see https://curl.se/libcurl/c/CURLOPT_SSLCERTTYPE.html
            $ext = pathinfo($cert, \PATHINFO_EXTENSION);
            if (preg_match('#^(der|p12)$#i', $ext)) {
                $conf[\Alma_Gateway_CURLOPT_SSLCERTTYPE] = strtoupper($ext);
            }
            $conf[\Alma_Gateway_CURLOPT_SSLCERT] = $cert;
        }

        if (isset($options['ssl_key'])) {
            if (\is_array($options['ssl_key'])) {
                if (\count($options['ssl_key']) === 2) {
                    [$sslKey, $conf[\Alma_Gateway_CURLOPT_SSLKEYPASSWD]] = $options['ssl_key'];
                } else {
                    [$sslKey] = $options['ssl_key'];
                }
            }

            $sslKey = $sslKey ?? $options['ssl_key'];

            if (!\file_exists($sslKey)) {
                throw new \InvalidArgumentException("SSL private key not found: {$sslKey}");
            }
            $conf[\Alma_Gateway_CURLOPT_SSLKEY] = $sslKey;
        }

        if (isset($options['progress'])) {
            $progress = $options['progress'];
            if (!\is_callable($progress)) {
                throw new \InvalidArgumentException('progress client option must be callable');
            }
            $conf[\Alma_Gateway_CURLOPT_NOPROGRESS] = false;
            $conf[\Alma_Gateway_CURLOPT_PROGRESSFUNCTION] = static function ($resource, int $downloadSize, int $downloaded, int $uploadSize, int $uploaded) use ($progress) {
                $progress($downloadSize, $downloaded, $uploadSize, $uploaded);
            };
        }

        if (!empty($options['debug'])) {
            $conf[\Alma_Gateway_CURLOPT_STDERR] = Utils::debugResource($options['debug']);
            $conf[\Alma_Gateway_CURLOPT_VERBOSE] = true;
        }
    }

    /**
     * This function ensures that a response was set on a transaction. If one
     * was not set, then the request is retried if possible. This error
     * typically means you are sending a payload, curl encountered a
     * "Connection died, retrying a fresh connect" error, tried to rewind the
     * stream, and then encountered a "necessary data rewind wasn't possible"
     * error, causing the request to be sent through curl_multi_info_read()
     * without an error status.
     *
     * @param callable(RequestInterface, array): PromiseInterface $handler
     */
    private static function retryFailedRewind(callable $handler, EasyHandle $easy, array $ctx): PromiseInterface
    {
        try {
            // Only rewind if the body has been read from.
            $body = $easy->request->getBody();
            if ($body->tell() > 0) {
                $body->rewind();
            }
        } catch (\RuntimeException $e) {
            $ctx['error'] = 'The connection unexpectedly failed without '
                .'providing an error. The request would have been retried, '
                .'but attempting to rewind the request body failed. '
                .'Exception: '.$e;

            return self::createRejection($easy, $ctx);
        }

        // Retry no more than 3 times before giving up.
        if (!isset($easy->options['_curl_retries'])) {
            $easy->options['_curl_retries'] = 1;
        } elseif ($easy->options['_curl_retries'] == 2) {
            $ctx['error'] = 'The cURL request was retried 3 times '
                .'and did not succeed. The most likely reason for the failure '
                .'is that cURL was unable to rewind the body of the request '
                .'and subsequent retries resulted in the same error. Turn on '
                .'the debug option to see what went wrong. See '
                .'https://bugs.php.net/bug.php?id=47204 for more information.';

            return self::createRejection($easy, $ctx);
        } else {
            ++$easy->options['_curl_retries'];
        }

        return $handler($easy->request, $easy->options);
    }

    private function createHeaderFn(EasyHandle $easy): callable
    {
        if (isset($easy->options['on_headers'])) {
            $onHeaders = $easy->options['on_headers'];

            if (!\is_callable($onHeaders)) {
                throw new \InvalidArgumentException('on_headers must be callable');
            }
        } else {
            $onHeaders = null;
        }

        return static function ($ch, $h) use (
            $onHeaders,
            $easy,
            &$startingResponse
        ) {
            $value = \trim($h);
            if ($value === '') {
                $startingResponse = true;
                try {
                    $easy->createResponse();
                } catch (\Alma_Gateway_Exception $e) {
                    $easy->createResponseException = $e;

                    return -1;
                }
                if ($onHeaders !== null) {
                    try {
                        $onHeaders($easy->response);
                    } catch (\Alma_Gateway_Exception $e) {
                        // Associate the exception with the handle and trigger
                        // a curl header write error by returning 0.
                        $easy->onHeadersException = $e;

                        return -1;
                    }
                }
            } elseif ($startingResponse) {
                $startingResponse = false;
                $easy->headers = [$value];
            } else {
                $easy->headers[] = $value;
            }

            return \strlen($h);
        };
    }

    public function __destruct()
    {
        foreach ($this->handles as $id => $handle) {
            if (PHP_VERSION_ID < 80000) {
                \curl_close($handle);
            }

            unset($this->handles[$id]);
        }
    }
}
