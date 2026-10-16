<?php declare(strict_types=1);

namespace HtmlPlucker\Internal;

use Dom\Element;
use Dom\ParentNode;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\SelectorException;
use HtmlPlucker\Node;

/**
 * CSS selector queries shared by Document and Node.
 *
 * The search is scoped to queryRoot(): the whole document for Document,
 * the node's subtree for Node.
 *
 * @internal Not part of the public API — may change without notice.
 */
trait QueryMethods
{
    /**
     * The node whose descendants are searched.
     */
    abstract private function queryRoot(): ParentNode;

    /**
     * Returns the first element matching $selector, or null.
     *
     * @throws SelectorException on an invalid selector
     */
    public function first(string $selector): ?Node
    {
        $element = Query::first($this->queryRoot(), $selector);

        return $element !== null ? new Node($element) : null;
    }

    /**
     * Returns all elements matching $selector, in document order.
     *
     * @return list<Node>
     *
     * @throws SelectorException on an invalid selector
     */
    public function all(string $selector): array
    {
        return array_map(
            static fn(Element $element): Node => new Node($element),
            Query::all($this->queryRoot(), $selector)
        );
    }

    /**
     * Returns true if at least one element matches $selector.
     *
     * @throws SelectorException on an invalid selector
     */
    public function has(string $selector): bool
    {
        return Query::first($this->queryRoot(), $selector) !== null;
    }

    /**
     * Returns the first element matching $selector.
     *
     * @throws NodeNotFoundException if nothing matches
     * @throws SelectorException     on an invalid selector
     */
    public function expect(string $selector): Node
    {
        return $this->first($selector)
            ?? throw new NodeNotFoundException($selector);
    }
}
