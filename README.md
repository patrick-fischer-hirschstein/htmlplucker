# HtmlPlucker

A modern, read-focused HTML scraping library for PHP 8.4+,
built on PHP's native `\Dom\HTMLDocument` (Lexbor HTML5 parser).

## Requirements

- PHP 8.4+
- ext-dom

## Installation

```bash
composer require patrick-fischer/html-plucker
```

## Quick Start

```php
use HtmlPlucker\Document;

$doc = Document::fromUrl('https://example.com');

// Optional element
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
Document::fromUrl(string $url): Document
Document::fromFile(string $path): Document
```

### Querying

```php
$doc->first(string $selector): ?Node
$doc->all(string $selector): Node[]
$doc->has(string $selector): bool
$doc->expect(string $selector): Node  // throws NodeNotFoundException
```

### Node

```php
$node->text(): string           // plaintext content
$node->html(): string           // inner HTML
$node->attr(string $name, ?string $default = null): ?string
$node->attrs(): array
$node->tag(): string
$node->first(string $selector): ?Node
$node->all(string $selector): Node[]
$node->has(string $selector): bool
$node->expect(string $selector): Node
$node->parent(): ?Node
$node->children(): Node[]
$node->next(): ?Node
$node->prev(): ?Node
```

## License

MIT
