<?php declare(strict_types=1);

namespace HtmlPlucker\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\TransferStats;
use HtmlPlucker\Exception\NetworkException;

/**
 * Default HTTP client implementation using Guzzle.
 *
 * Handles redirects, timeouts and maps Guzzle exceptions
 * to HtmlPlucker's NetworkException.
 */
final class GuzzleHttpClient implements HttpClientInterface
{
    private const array DEFAULT_HEADERS = [
        'User-Agent' => 'HtmlPlucker/1.0',
    ];

    private readonly Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function get(
        string $url,
        int    $timeout = 10,
        array  $headers = []
    ): HttpResponse {
        $effectiveUrl = $url;

        try {
            $response = $this->client->get($url, [
                'timeout'         => $timeout,
                'connect_timeout' => 5,
                'allow_redirects' => ['max' => 5],
                'headers'         => self::mergeHeaders($headers),
                // Called once per transfer; after redirects the last call wins
                'on_stats'        => static function (TransferStats $stats) use (&$effectiveUrl): void {
                    $effectiveUrl = (string) $stats->getEffectiveUri();
                },
            ]);

            return new HttpResponse(
                body:         (string) $response->getBody(),
                statusCode:   $response->getStatusCode(),
                charset:      self::charsetFromContentType($response->getHeaderLine('Content-Type')),
                effectiveUrl: $effectiveUrl,
            );

        } catch (GuzzleException $e) {
            throw new NetworkException(
                message:    "Could not fetch URL: \"{$url}\" — {$e->getMessage()}",
                // Only HTTP error responses (4xx/5xx) carry a status; transfer
                // failures after a non-error response (e.g. body timeout on 200,
                // too many redirects) are reported without one.
                statusCode: $e instanceof BadResponseException
                    ? $e->getResponse()->getStatusCode()
                    : null,
                previous:   $e
            );
        }
    }

    /**
     * Adds the default headers unless the caller already set them.
     * Header names are compared case-insensitively.
     *
     * @param  array<string, string> $headers
     * @return array<string, string>
     */
    private static function mergeHeaders(array $headers): array
    {
        $given = array_change_key_case($headers, CASE_LOWER);

        $defaults = array_filter(
            self::DEFAULT_HEADERS,
            static fn(string $name): bool => !isset($given[strtolower($name)]),
            ARRAY_FILTER_USE_KEY
        );

        return $defaults + $headers;
    }

    /**
     * Extracts the charset parameter from a Content-Type header value,
     * e.g. 'text/html; charset="ISO-8859-1"' -> 'ISO-8859-1'.
     */
    private static function charsetFromContentType(string $contentType): ?string
    {
        if (preg_match('/;\s*charset\s*=\s*"?([^";\s]+)"?/i', $contentType, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
