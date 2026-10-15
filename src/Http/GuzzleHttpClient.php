<?php declare(strict_types=1);

namespace HtmlPlucker\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\TransferStats;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\SizeLimitExceededException;
use HtmlPlucker\Internal\LimitedSink;
use HtmlPlucker\Limits;
use Psr\Http\Message\ResponseInterface;

/**
 * Default HTTP client implementation using Guzzle.
 *
 * Handles redirects, timeouts and maps Guzzle exceptions
 * to HtmlPlucker's NetworkException.
 *
 * Response bodies are limited to $maxBytes (default 10 MiB, null = no
 * limit): an oversized Content-Length aborts before the body is read,
 * otherwise the transfer aborts as soon as the decoded body exceeds the
 * limit. Both cases throw SizeLimitExceededException.
 */
final class GuzzleHttpClient implements HttpClientInterface
{
    private const array DEFAULT_HEADERS = [
        'User-Agent' => 'HtmlPlucker/1.0',
    ];

    private readonly Client $client;

    /**
     * @param int|null $maxBytes Maximum decoded body size in bytes, null for no limit
     *
     * @throws \InvalidArgumentException if $maxBytes is not positive
     */
    public function __construct(
        ?Client                  $client = null,
        private readonly ?int    $maxBytes = Limits::DEFAULT_MAX_BYTES,
    ) {
        if ($maxBytes !== null && $maxBytes < 1) {
            throw new \InvalidArgumentException('$maxBytes must be a positive integer or null.');
        }

        $this->client = $client ?? new Client();
    }

    public function get(
        string $url,
        int    $timeout = 10,
        array  $headers = []
    ): HttpResponse {
        $effectiveUrl = $url;

        $options = [
            'timeout'         => $timeout,
            'connect_timeout' => 5,
            'allow_redirects' => ['max' => 5],
            'headers'         => self::mergeHeaders($headers),
            // Called once per transfer; after redirects the last call wins
            'on_stats'        => static function (TransferStats $stats) use (&$effectiveUrl): void {
                $effectiveUrl = (string) $stats->getEffectiveUri();
            },
        ];

        if ($this->maxBytes !== null) {
            $options += self::limitOptions($url, $this->maxBytes);
        }

        try {
            $response = $this->client->get($url, $options);

            return new HttpResponse(
                body:         (string) $response->getBody(),
                statusCode:   $response->getStatusCode(),
                charset:      self::charsetFromContentType($response->getHeaderLine('Content-Type')),
                effectiveUrl: $effectiveUrl,
            );

        } catch (GuzzleException $e) {
            // Guzzle wraps exceptions thrown by on_headers and the sink
            $sizeLimit = self::findSizeLimitException($e);

            if ($sizeLimit !== null) {
                throw $sizeLimit;
            }

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
     * Request options enforcing the body size limit.
     *
     * @return array{sink: LimitedSink, on_headers: callable(ResponseInterface): void}
     */
    private static function limitOptions(string $url, int $maxBytes): array
    {
        return [
            // Receives the decoded body; throws (and aborts) beyond $maxBytes
            'sink'       => new LimitedSink($url, $maxBytes),
            // Fail fast before any body bytes arrive
            'on_headers' => static function (ResponseInterface $response) use ($url, $maxBytes): void {
                $length = $response->getHeaderLine('Content-Length');

                if (ctype_digit($length) && (int) $length > $maxBytes) {
                    throw new SizeLimitExceededException($url, $maxBytes, (int) $length);
                }
            },
        ];
    }

    private static function findSizeLimitException(\Throwable $e): ?SizeLimitExceededException
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof SizeLimitExceededException) {
                return $current;
            }
        }

        return null;
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
