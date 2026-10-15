<?php declare(strict_types=1);

namespace HtmlPlucker\Internal;

use Dom\Document;
use GuzzleHttp\Psr7\Exception\MalformedUriException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * Resolves URLs against a document's base URL, following the HTML spec:
 * the first <base href>, resolved against the document URL; otherwise
 * the document URL itself.
 *
 * PHP's Dom\Node::$baseURI is not used — it follows XML rules (xml:base)
 * and ignores <base href>.
 *
 * @internal Not part of the public API — may change without notice.
 */
final class UrlResolver
{
    private function __construct() {}

    /**
     * Returns $url resolved to an absolute URL, or unchanged if no
     * absolute base URL is known or $url cannot be parsed.
     */
    public static function resolve(Document $document, string $url): string
    {
        $base = self::baseUrl($document);
        $rel  = self::parse($url);

        if ($base === null || $rel === null) {
            return $url;
        }

        return (string) UriResolver::resolve($base, $rel);
    }

    private static function baseUrl(Document $document): ?UriInterface
    {
        // PHP reports "about:blank" when no document URL was set
        $base = $document->documentURI === 'about:blank'
            ? null
            : self::parse($document->documentURI);

        $baseElement = Query::first($document, 'base[href]');
        $href        = self::parse(trim($baseElement?->getAttribute('href') ?? ''));

        if ($href !== null) {
            $base = $base !== null ? UriResolver::resolve($base, $href) : $href;
        }

        return $base !== null && Uri::isAbsolute($base) ? $base : null;
    }

    private static function parse(string $url): ?UriInterface
    {
        if ($url === '') {
            return null;
        }

        try {
            return new Uri($url);
        } catch (MalformedUriException) {
            return null;
        }
    }
}
