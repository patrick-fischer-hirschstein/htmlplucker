# HtmlPlucker

[![CI](https://github.com/patrick-fischer-hirschstein/htmlplucker/actions/workflows/ci.yml/badge.svg)](https://github.com/patrick-fischer-hirschstein/htmlplucker/actions/workflows/ci.yml)

A modern, read-focused HTML scraping library for PHP 8.4+,
built on PHP's native `\Dom\HTMLDocument` (Lexbor HTML5 parser)
with Guzzle as HTTP client.

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
Document::fromString(string $html): Document
Document::fromFile(string $path): Document
Document::fromUrl(
    string $url,
    int $timeout = 10,
    array $headers = [],
    ?HttpClientInterface $client = null
): Document
```

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
$node->text(): string                              // plaintext, no tags
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
└── NodeNotFoundException     expect() found nothing
```

## Custom HTTP Client

`fromUrl()` accepts any `HttpClientInterface` implementation.
Swap in your own client for proxy support, custom auth, etc.:

```php
use HtmlPlucker\Http\HttpClientInterface;

final class MyClient implements HttpClientInterface
{
    public function get(string $url, int $timeout = 10, array $headers = []): string
    {
        // your implementation
    }
}

$doc = Document::fromUrl($url, client: new MyClient());
```

## Testing

Use `MockHttpClient` to test code that calls `Document::fromUrl()`
without making real HTTP requests:

```php
use HtmlPlucker\Tests\Support\MockHttpClient;

$mock = (new MockHttpClient())
    ->addResponse('https://example.com', '<h1>Mocked</h1>');

$doc = Document::fromUrl('https://example.com', client: $mock);
$doc->expect('h1')->text(); // 'Mocked'

$mock->wasRequested('https://example.com'); // true
```

## License

MIT
