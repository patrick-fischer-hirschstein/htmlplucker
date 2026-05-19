<?php declare(strict_types=1);

namespace HtmlPlucker;

use Dom\Element;
use Dom\HTMLDocument;
use HtmlPlucker\Exception\FileNotFoundException;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\ParseException;

/**
 * Entry point for HtmlPlucker.
 *
 * Load an HTML document from a string, URL or file,
 * then query it with CSS selectors.
 *
 * @example
 *   $doc = Document::fromUrl('https://example.com');
 *   $title = $doc->expect('h1')->text();
 *   $links = $doc->all('a[href]');
 */
final class Document
{
    private HTMLDocument $dom;

    private function __construct(HTMLDocument $dom)
    {
        $this->dom = $dom;
    }

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    /**
     * Parses an HTML string.
     *
     * @throws ParseException if $html is empty
     */
    public static function fromString(string $html): self
    {
        if (trim($html) === '') {
            throw new ParseException('HTML string must not be empty.');
        }

        return new self(
            HTMLDocument::createFromString($html, LIBXML_NOERROR)
        );
    }

    /**
     * Fetches a URL and parses the response body as HTML.
     *
     * @throws NetworkException if the URL cannot be fetched or returns an HTTP error
     */
    public static function fromUrl(
        string $url,
        int    $timeoutSeconds = 10,
        array  $headers        = []
    ): self {
        $context = stream_context_create([
            'http' => [
                'timeout'          => $timeoutSeconds,
                'follow_location'  => true,
                'max_redirects'    => 5,
                'ignore_errors'    => true,
                'protocol_version' => '1.1',
                'header'           => array_merge(
                    ['User-Agent: HtmlPlucker/1.0'],
                    $headers
                ),
            ],
            'https' => [
                'timeout'         => $timeoutSeconds,
                'follow_location' => true,
                'max_redirects'   => 5,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            throw new NetworkException(
                "Could not fetch URL: \"{$url}\""
            );
        }

        // Check HTTP status code
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('/HTTP\/\S+\s+(\d{3})/', $statusLine, $m)) {
            $status = (int) $m[1];
            if ($status >= 400) {
                throw new NetworkException(
                    "HTTP {$status} for URL: \"{$url}\""
                );
            }
        }

        return self::fromString($html);
    }

    /**
     * Reads a file from disk and parses it as HTML.
     *
     * @throws FileNotFoundException if the file does not exist or is not readable
     */
    public static function fromFile(string $path): self
    {
        if (!is_readable($path)) {
            throw new FileNotFoundException(
                "File not found or not readable: \"{$path}\""
            );
        }

        $html = file_get_contents($path);

        if ($html === false) {
            throw new FileNotFoundException(
                "Could not read file: \"{$path}\""
            );
        }

        return self::fromString($html);
    }

    // -------------------------------------------------------------------------
    // Querying
    // -------------------------------------------------------------------------

    /**
     * Returns the first element matching $selector, or null.
     */
    public function first(string $selector): ?Node
    {
        $element = $this->dom->querySelector($selector);

        return $element instanceof Element ? new Node($element) : null;
    }

    /**
     * Returns all elements matching $selector.
     *
     * @return Node[]
     */
    public function all(string $selector): array
    {
        $result = [];

        foreach ($this->dom->querySelectorAll($selector) as $element) {
            if ($element instanceof Element) {
                $result[] = new Node($element);
            }
        }

        return $result;
    }

    /**
     * Returns true if at least one element matches $selector.
     */
    public function has(string $selector): bool
    {
        return $this->dom->querySelector($selector) !== null;
    }

    /**
     * Returns the first element matching $selector.
     *
     * @throws NodeNotFoundException
     */
    public function expect(string $selector): Node
    {
        return $this->first($selector)
            ?? throw new NodeNotFoundException($selector);
    }
}
