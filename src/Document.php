<?php declare(strict_types=1);

namespace HtmlPlucker;

use Dom\Element;
use Dom\HTMLDocument;
use HtmlPlucker\Exception\FileNotFoundException;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\ParseException;
use HtmlPlucker\Exception\SelectorException;
use HtmlPlucker\Http\GuzzleHttpClient;
use HtmlPlucker\Http\HttpClientInterface;
use HtmlPlucker\Internal\Query;

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

    /**
     * @param string|null $baseUrl Absolute URL used to resolve relative links
     *                             (Node::url()); stored as the DOM's documentURI
     */
    private function __construct(HTMLDocument $dom, ?string $baseUrl = null)
    {
        $this->dom = $dom;

        if ($baseUrl !== null) {
            $this->dom->documentURI = $baseUrl;
        }
    }

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    /**
     * Parses an HTML string.
     *
     * Without $charset the encoding is detected from a BOM or
     * <meta charset>; pass e.g. 'ISO-8859-1' to override detection.
     *
     * $baseUrl is the absolute URL relative links are resolved against
     * by Node::url() (a <base href> in the document takes precedence).
     *
     * @throws ParseException if $html is empty or $charset is not supported
     */
    public static function fromString(
        string  $html,
        ?string $charset = null,
        ?string $baseUrl = null
    ): self {
        try {
            return new self(self::createDom($html, $charset), $baseUrl);
        } catch (\ValueError $e) {
            throw new ParseException(
                "Could not parse HTML: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Fetches a URL and parses the response body as HTML.
     *
     * The charset from the Content-Type header is used for decoding.
     * If the server names an unknown charset, encoding detection falls
     * back to BOM / <meta charset>.
     *
     * The final URL after redirects becomes the base URL for Node::url().
     *
     * Provide a custom $client to use a different HTTP implementation
     * or to inject a MockHttpClient in tests.
     *
     * @param array<string, string> $headers Additional request headers, name => value
     *
     * @throws NetworkException if the URL cannot be fetched or returns an HTTP error
     * @throws ParseException if the response body is empty
     */
    public static function fromUrl(
        string              $url,
        int                 $timeout = 10,
        array               $headers = [],
        ?HttpClientInterface $client  = null
    ): self {
        $client ??= new GuzzleHttpClient();

        $response = $client->get($url, $timeout, $headers);

        $baseUrl  = $response->effectiveUrl;

        if ($response->charset === null) {
            return self::fromString($response->body, baseUrl: $baseUrl);
        }

        try {
            return new self(self::createDom($response->body, $response->charset), $baseUrl);
        } catch (\ValueError) {
            // Server sent an unsupported charset name (e.g. "charset=none")
            return self::fromString($response->body, baseUrl: $baseUrl);
        }
    }

    /**
     * Reads a file from disk and parses it as HTML.
     *
     * @see fromString() for the $charset and $baseUrl parameters
     *
     * @throws FileNotFoundException if the file does not exist or is not readable
     * @throws ParseException if the file is empty or $charset is not supported
     */
    public static function fromFile(
        string  $path,
        ?string $charset = null,
        ?string $baseUrl = null
    ): self {
        if (!is_file($path) || !is_readable($path)) {
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

        return self::fromString($html, $charset, $baseUrl);
    }

    /**
     * Encoding precedence follows the HTML spec:
     * BOM > $charset > <meta charset> > UTF-8.
     *
     * PHP skips BOM sniffing entirely when an override encoding is passed
     * (and leaves the BOM bytes in the content), so $charset is dropped
     * whenever the input starts with a BOM.
     *
     * @throws ParseException if $html is empty
     * @throws \ValueError    if $charset is not a supported encoding
     */
    private static function createDom(string $html, ?string $charset): HTMLDocument
    {
        if (trim($html) === '') {
            throw new ParseException('HTML string must not be empty.');
        }

        if ($charset !== null && self::startsWithBom($html)) {
            $charset = null;
        }

        return HTMLDocument::createFromString($html, LIBXML_NOERROR, $charset);
    }

    /**
     * Detects a UTF-8, UTF-16BE or UTF-16LE byte order mark.
     */
    private static function startsWithBom(string $html): bool
    {
        return str_starts_with($html, "\xEF\xBB\xBF")
            || str_starts_with($html, "\xFE\xFF")
            || str_starts_with($html, "\xFF\xFE");
    }

    // -------------------------------------------------------------------------
    // Querying
    // -------------------------------------------------------------------------

    /**
     * Returns the first element matching $selector, or null.
     *
     * @throws SelectorException on an invalid selector
     */
    public function first(string $selector): ?Node
    {
        $element = Query::first($this->dom, $selector);

        return $element !== null ? new Node($element) : null;
    }

    /**
     * Returns all elements matching $selector.
     *
     * @return list<Node>
     *
     * @throws SelectorException on an invalid selector
     */
    public function all(string $selector): array
    {
        return array_map(
            static fn(Element $element): Node => new Node($element),
            Query::all($this->dom, $selector)
        );
    }

    /**
     * Returns true if at least one element matches $selector.
     *
     * @throws SelectorException on an invalid selector
     */
    public function has(string $selector): bool
    {
        return Query::first($this->dom, $selector) !== null;
    }

    /**
     * Returns the first element matching $selector.
     *
     * @throws NodeNotFoundException
     * @throws SelectorException on an invalid selector
     */
    public function expect(string $selector): Node
    {
        return $this->first($selector)
            ?? throw new NodeNotFoundException($selector);
    }
}
