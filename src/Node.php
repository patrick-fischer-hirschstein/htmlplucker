<?php declare(strict_types=1);

namespace HtmlPlucker;

use Dom\Element;
use Dom\Text;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\SelectorException;
use HtmlPlucker\Internal\Query;
use HtmlPlucker\Internal\UrlResolver;

/**
 * Wraps a \Dom\Element and provides a clean read API.
 */
final class Node
{
    public function __construct(
        private readonly Element $element
    ) {}

    // -------------------------------------------------------------------------
    // Content
    // -------------------------------------------------------------------------

    /**
     * Returns the normalized plaintext content of this element.
     *
     * - Whitespace runs (incl. &nbsp; and other Unicode spaces) collapse
     *   to a single space, the result is trimmed.
     * - Block element boundaries and <br> act as word separators,
     *   so "<td>1</td><td>2</td>" yields "1 2".
     * - Content of <script>, <style> and <template> is ignored.
     *
     * Use rawText() for the unmodified textContent.
     */
    public function text(): string
    {
        $parts = [];
        self::collectText($this->element, $parts);

        return trim(
            (string) preg_replace('/[\s\p{Z}]+/u', ' ', implode('', $parts))
        );
    }

    /**
     * Returns the unmodified textContent of this element,
     * including all whitespace and script/style content.
     */
    public function rawText(): string
    {
        return $this->element->textContent ?? '';
    }

    /**
     * Returns the inner HTML of this element.
     */
    public function html(): string
    {
        return $this->element->innerHTML ?? '';
    }

    /**
     * Returns the tag name (e.g. "div", "a", "td").
     *
     * HTML element names are always lowercase; SVG and MathML elements
     * keep their spec-defined case (e.g. "foreignObject", "linearGradient").
     */
    public function tag(): string
    {
        return $this->element->localName;
    }

    // -------------------------------------------------------------------------
    // Attributes
    // -------------------------------------------------------------------------

    /**
     * Returns the value of a single attribute, or $default if not present.
     */
    public function attr(string $name, ?string $default = null): ?string
    {
        if (!$this->element->hasAttribute($name)) {
            return $default;
        }

        return $this->element->getAttribute($name);
    }

    /**
     * Returns a URL attribute (href, src, action, …) as an absolute URL.
     *
     * Relative values are resolved against the document's base URL:
     * the first <base href> in the document, itself resolved against
     * the URL the document was loaded from (fromUrl(), after redirects)
     * or the $baseUrl passed to fromString()/fromFile().
     * Without an absolute base URL — or if the value cannot be parsed —
     * the trimmed value is returned unchanged.
     *
     * @return string|null null if the attribute is missing or empty
     */
    public function url(string $attribute = 'href'): ?string
    {
        $value = trim($this->attr($attribute) ?? '');

        if ($value === '') {
            return null;
        }

        $document = $this->element->ownerDocument;

        return $document !== null
            ? UrlResolver::resolve($document, $value)
            : $value;
    }

    /**
     * Returns all attributes as an associative array.
     *
     * @return array<string, string> name => value
     */
    public function attrs(): array
    {
        $result = [];

        foreach ($this->element->attributes as $attr) {
            $result[$attr->name] = $attr->value;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Querying within this node's subtree
    // -------------------------------------------------------------------------

    /**
     * Returns the first element matching $selector within this node, or null.
     *
     * @throws SelectorException on an invalid selector
     */
    public function first(string $selector): ?self
    {
        $element = Query::first($this->element, $selector);

        return $element !== null ? new self($element) : null;
    }

    /**
     * Returns all elements matching $selector within this node.
     *
     * @return list<self>
     *
     * @throws SelectorException on an invalid selector
     */
    public function all(string $selector): array
    {
        return array_map(
            static fn(Element $element): self => new self($element),
            Query::all($this->element, $selector)
        );
    }

    /**
     * Returns true if at least one element matches $selector within this node.
     *
     * @throws SelectorException on an invalid selector
     */
    public function has(string $selector): bool
    {
        return Query::first($this->element, $selector) !== null;
    }

    /**
     * Returns the first element matching $selector within this node.
     *
     * @throws NodeNotFoundException
     * @throws SelectorException on an invalid selector
     */
    public function expect(string $selector): self
    {
        return $this->first($selector)
            ?? throw new NodeNotFoundException($selector);
    }

    // -------------------------------------------------------------------------
    // Traversal
    // -------------------------------------------------------------------------

    /**
     * Returns the parent element, or null if this is the root.
     */
    public function parent(): ?self
    {
        $parent = $this->element->parentElement;

        return $parent !== null ? new self($parent) : null;
    }

    /**
     * Returns all direct child elements.
     *
     * @return self[]
     */
    public function children(): array
    {
        $result = [];

        foreach ($this->element->childNodes as $child) {
            if ($child instanceof Element) {
                $result[] = new self($child);
            }
        }

        return $result;
    }

    /**
     * Returns the next sibling element, or null.
     */
    public function next(): ?self
    {
        $sibling = $this->element->nextElementSibling;

        return $sibling !== null ? new self($sibling) : null;
    }

    /**
     * Returns the previous sibling element, or null.
     */
    public function prev(): ?self
    {
        $sibling = $this->element->previousElementSibling;

        return $sibling !== null ? new self($sibling) : null;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /** Elements whose content never counts as visible text. */
    private const array SKIPPED_TAGS = [
        'script' => true, 'style' => true, 'template' => true,
    ];

    /** Elements whose boundaries separate words. */
    private const array BLOCK_TAGS = [
        'address' => true, 'article' => true, 'aside' => true,
        'blockquote' => true, 'br' => true, 'caption' => true,
        'dd' => true, 'details' => true, 'dialog' => true, 'div' => true,
        'dl' => true, 'dt' => true, 'fieldset' => true,
        'figcaption' => true, 'figure' => true, 'footer' => true,
        'form' => true, 'h1' => true, 'h2' => true, 'h3' => true,
        'h4' => true, 'h5' => true, 'h6' => true, 'header' => true,
        'hgroup' => true, 'hr' => true, 'legend' => true, 'li' => true,
        'main' => true, 'nav' => true, 'ol' => true, 'option' => true,
        'p' => true, 'pre' => true, 'section' => true, 'summary' => true,
        'table' => true, 'tbody' => true, 'td' => true, 'tfoot' => true,
        'th' => true, 'thead' => true, 'tr' => true, 'ul' => true,
    ];

    /**
     * Collects text node data depth-first, inserting a space
     * around block elements.
     *
     * @param list<string> $parts
     */
    private static function collectText(Element $element, array &$parts): void
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof Text) {
                $parts[] = $child->data;
                continue;
            }

            if (!$child instanceof Element || isset(self::SKIPPED_TAGS[$child->localName])) {
                continue;
            }

            $isBlock = isset(self::BLOCK_TAGS[$child->localName]);

            if ($isBlock) {
                $parts[] = ' ';
            }

            self::collectText($child, $parts);

            if ($isBlock) {
                $parts[] = ' ';
            }
        }
    }
}
