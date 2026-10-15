<?php declare(strict_types=1);

namespace HtmlPlucker\Http;

use HtmlPlucker\Exception\NetworkException;

/**
 * Minimal HTTP client contract for fetching HTML documents.
 *
 * The default implementation uses Guzzle. In tests, swap it for
 * MockHttpClient without touching production code.
 */
interface HttpClientInterface
{
    /**
     * Performs a GET request and returns body, status, charset and final URL.
     *
     * @param string                $url     Target URL
     * @param int                   $timeout Timeout in seconds
     * @param array<string, string> $headers Additional request headers,
     *                                       name => value
     *
     * @throws NetworkException on connection failure or HTTP 4xx/5xx
     */
    public function get(
        string $url,
        int    $timeout = 10,
        array  $headers = []
    ): HttpResponse;
}
