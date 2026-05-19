<?php declare(strict_types=1);

namespace HtmlPlucker;

use Dom\Element;
use HtmlPlucker\Exception\NodeNotFoundException;

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
     * Returns the plaintext content of this element (no HTML tags).
     */
    public function text(): string
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
     * Returns the tag name in lowercase (e.g. "div", "a", "td").
     */
    public function tag(): string
    {
        return strtolower($this->element->localName);
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
     * Returns all attributes as an associative array.
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
     */
    public function first(string $selector): ?self
    {
        $element = $this->element->querySelector($selector);

        return $element !== null ? new self($element) : null;
    }

    /**
     * Returns all elements matching $selector within this node.
     *
     * @return self[]
     */
    public function all(string $selector): array
    {
        return self::nodeListToArray(
            $this->element->querySelectorAll($selector)
        );
    }

    /**
     * Returns true if at least one element matches $selector within this node.
     */
    public function has(string $selector): bool
    {
        return $this->element->querySelector($selector) !== null;
    }

    /**
     * Returns the first element matching $selector within this node.
     *
     * @throws NodeNotFoundException
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

        foreach ($this->element->children as $child) {
            $result[] = new self($child);
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

    /**
     * @return self[]
     */
    private static function nodeListToArray(\Dom\NodeList $list): array
    {
        $result = [];

        foreach ($list as $element) {
            if ($element instanceof Element) {
                $result[] = new self($element);
            }
        }

        return $result;
    }
}
