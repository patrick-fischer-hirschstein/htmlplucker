<?php declare(strict_types=1);

namespace HtmlPlucker\Testing;

use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Http\HttpClientInterface;
use HtmlPlucker\Http\HttpResponse;

/**
 * In-memory HTTP client for unit tests.
 *
 * Register responses per URL, then inspect recorded requests afterwards.
 *
 * @example
 *   $mock = new MockHttpClient();
 *   $mock->addResponse('https://example.com', '<h1>Hello</h1>');
 *
 *   $doc = Document::fromUrl('https://example.com', client: $mock);
 *   $doc->expect('h1')->text(); // 'Hello'
 *
 *   $mock->wasRequested('https://example.com'); // true
 */
final class MockHttpClient implements HttpClientInterface
{
    /** @var array<string, array{html: string, status: int, charset: ?string}> */
    private array $responses = [];

    /** @var list<array{url: string, timeout: int, headers: array<string, string>}> */
    private array $requests = [];

    // -------------------------------------------------------------------------
    // Setup
    // -------------------------------------------------------------------------

    /**
     * Register a response for the given URL.
     */
    public function addResponse(
        string  $url,
        string  $html,
        int     $status = 200,
        ?string $charset = null
    ): self {
        $this->responses[$url] = [
            'html'    => $html,
            'status'  => $status,
            'charset' => $charset,
        ];

        return $this;
    }

    /**
     * Register a response that will be returned for ANY URL.
     * Useful when the exact URL doesn't matter for the test.
     */
    public function addDefaultResponse(
        string  $html,
        int     $status = 200,
        ?string $charset = null
    ): self {
        return $this->addResponse('*', $html, $status, $charset);
    }

    // -------------------------------------------------------------------------
    // HttpClientInterface
    // -------------------------------------------------------------------------

    public function get(
        string $url,
        int    $timeout = 10,
        array  $headers = []
    ): HttpResponse {
        $this->requests[] = [
            'url'     => $url,
            'timeout' => $timeout,
            'headers' => $headers,
        ];

        $response = $this->responses[$url]
            ?? $this->responses['*']
            ?? null;

        if ($response === null) {
            throw new NetworkException(
                "MockHttpClient: no response registered for \"{$url}\""
            );
        }

        if ($response['status'] >= 400) {
            throw new NetworkException(
                message:    "MockHttpClient: HTTP {$response['status']} for \"{$url}\"",
                statusCode: $response['status']
            );
        }

        return new HttpResponse(
            body:         $response['html'],
            statusCode:   $response['status'],
            charset:      $response['charset'],
            effectiveUrl: $url,
        );
    }

    // -------------------------------------------------------------------------
    // Test assertions
    // -------------------------------------------------------------------------

    /**
     * Returns all recorded requests.
     *
     * @return list<array{url: string, timeout: int, headers: array<string, string>}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    public function wasRequested(string $url): bool
    {
        foreach ($this->requests as $request) {
            if ($request['url'] === $url) {
                return true;
            }
        }

        return false;
    }
}
