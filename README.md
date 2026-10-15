# HtmlPlucker

[![CI](https://github.com/patrick-fischer-hirschstein/htmlplucker/actions/workflows/ci.yml/badge.svg)](https://github.com/patrick-fischer-hirschstein/htmlplucker/actions/workflows/ci.yml)

Modern PHP 8.4+ HTML scraping library built on Dom\HTMLDocument
— clean API, Guzzle HTTP, CSS selectors.

## Requirements

- PHP 8.4+
- ext-dom
- guzzlehttp/guzzle ^7.9

## Installation

```bash
composer require patrick-fischer/html-plucker
```

## Quick Start

```php
use HtmlPlucker\Document;

// Load from URL
$doc = Document::fromUrl('https://example.com');

// Load from string or file
$doc = Document::fromString('<h1>Hello</h1>');
$doc = Document::fromFile('/path/to/page.html');

// Optional element — returns null if not found
$subtitle = $doc->first('h2')?->text();

// Required element — throws NodeNotFoundException if missing
$title = $doc->expect('h1')->text();

// All matching elements
foreach ($doc->all('table tr') as $row) {
    echo $row->first('td')?->text();
}
```

## API

### Loading

```php
Document::fromString(string $html, ?string $charset = null): Document
Document::fromFile(string $path, ?string $charset = null): Document
Document::fromUrl(
    string $url,
    int $timeout = 10,
    array $headers = [],
    ?HttpClientInterface $client = null
): Document
```

### Character encoding

The encoding is determined in this order (as specified for HTML):

1. Byte order mark (UTF-8, UTF-16BE, UTF-16LE)
2. `$charset` parameter — or, for `fromUrl()`, the charset from the
   `Content-Type` response header
3. `<meta charset>` within the first 1024 bytes
4. UTF-8

```php
// Legacy page without <meta charset>, known to be Latin-1
$doc = Document::fromFile('/path/to/old-page.html', 'ISO-8859-1');
```

If a server sends an unknown charset name (e.g. `charset=none`),
`fromUrl()` ignores it and falls back to steps 3–4. An invalid
`$charset` passed to `fromString()`/`fromFile()` throws a `ParseException`.

### Querying (Document and Node)

```php
->first(string $selector): ?Node       // first match or null
->all(string $selector): Node[]        // all matches
->has(string $selector): bool          // true if at least one match
->expect(string $selector): Node       // first match or NodeNotFoundException
```

### Node

```php
// Content
$node->text(): string                              // normalized plaintext (see below)
$node->rawText(): string                           // unmodified textContent
$node->html(): string                              // inner HTML
$node->tag(): string                               // lowercase tag name

// Attributes
$node->attr(string $name, ?string $default): ?string
$node->attrs(): array                              // all attributes

// Traversal
$node->parent(): ?Node
$node->children(): Node[]
$node->next(): ?Node
$node->prev(): ?Node
```

`text()` collapses all whitespace (including `&nbsp;`) to single spaces,
trims the result, ignores `<script>`, `<style>` and `<template>`,
and treats block element boundaries and `<br>` as word separators:

```php
// <tr><td>1</td><td>FC Example</td></tr>
$row->text();    // "1 FC Example"
$row->rawText(); // "1FC Example"
```

## Error Handling

```php
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\FileNotFoundException;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\ParseException;

try {
    $doc = Document::fromUrl($url);
} catch (NetworkException $e) {
    // Connection failed or HTTP 4xx/5xx
    // $e->statusCode: e.g. 404, 429, 503 — null if no response was received
}

// Optional — no exception, check for null
$price = $doc->first('.price')?->text() ?? 'n/a';

// Required — throws if not found
$title = $doc->expect('h1')->text();
```

**Exception hierarchy:**
```
HtmlPluckerException
├── LoadException
│   ├── NetworkException      URL not reachable or HTTP error
│   └── FileNotFoundException File does not exist
├── ParseException            Empty input
├── SelectorException         Invalid CSS selector
└── NodeNotFoundException     expect() found nothing
```

## Custom HTTP Client

`fromUrl()` accepts any `HttpClientInterface` implementation.
Swap in your own client for proxy support, custom auth, etc.:

```php
use HtmlPlucker\Http\HttpClientInterface;
use HtmlPlucker\Http\HttpResponse;

final class MyClient implements HttpClientInterface
{
    public function get(string $url, int $timeout = 10, array $headers = []): HttpResponse
    {
        // your implementation — throw NetworkException on failure / HTTP 4xx/5xx
        return new HttpResponse(
            body:         $body,
            statusCode:   200,
            charset:      'UTF-8',   // from Content-Type, or null
            effectiveUrl: $finalUrl, // URL after redirects
        );
    }
}

$doc = Document::fromUrl($url, client: new MyClient());
```

### Upgrading from 1.0

`HttpClientInterface::get()` returns an `HttpResponse` instead of the body
string. Custom implementations need to wrap their result as shown above.
Code that only uses `Document::fromUrl()` is not affected.

## Testing

Use `MockHttpClient` to test code that calls `Document::fromUrl()`
without making real HTTP requests:

```php
use HtmlPlucker\Testing\MockHttpClient;

$mock = (new MockHttpClient())
    ->addResponse('https://example.com', '<h1>Mocked</h1>');

$doc = Document::fromUrl('https://example.com', client: $mock);
$doc->expect('h1')->text(); // 'Mocked'

$mock->wasRequested('https://example.com'); // true
```

## License

MIT
