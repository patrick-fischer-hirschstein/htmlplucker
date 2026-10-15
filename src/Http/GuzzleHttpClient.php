<?php declare(strict_types=1);

namespace HtmlPlucker\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
    ): string {
        try {
            $response = $this->client->get($url, [
                'timeout'         => $timeout,
                'connect_timeout' => 5,
                'allow_redirects' => ['max' => 5],
                'headers'         => self::mergeHeaders($headers),
            ]);

            return (string) $response->getBody();

        } catch (GuzzleException $e) {
            throw new NetworkException(
                message:  "Could not fetch URL: \"{$url}\" — {$e->getMessage()}",
                previous: $e
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
}
