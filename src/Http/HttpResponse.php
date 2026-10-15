<?php declare(strict_types=1);

namespace HtmlPlucker\Http;

/**
 * Result of a successful HTTP GET request.
 */
final readonly class HttpResponse
{
    /**
     * @param string      $body         Raw response body
     * @param int         $statusCode   HTTP status (2xx/3xx — errors throw NetworkException)
     * @param string|null $charset      Charset from the Content-Type header, null if absent
     * @param string      $effectiveUrl Final URL after following redirects
     */
    public function __construct(
        public string  $body,
        public int     $statusCode,
        public ?string $charset,
        public string  $effectiveUrl,
    ) {}
}
