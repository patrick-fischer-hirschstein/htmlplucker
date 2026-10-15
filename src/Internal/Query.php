<?php declare(strict_types=1);

namespace HtmlPlucker\Internal;

use Dom\Element;
use Dom\ParentNode;
use HtmlPlucker\Exception\SelectorException;

/**
 * Runs CSS selector queries and translates native DOM errors
 * into HtmlPlucker exceptions.
 *
 * @internal Not part of the public API — may change without notice.
 */
final class Query
{
    private function __construct() {}

    /**
     * @throws SelectorException on an invalid selector
     */
    public static function first(ParentNode $context, string $selector): ?Element
    {
        try {
            return $context->querySelector($selector);
        } catch (\DOMException $e) {
            throw new SelectorException($selector, $e);
        }
    }

    /**
     * @return list<Element>
     *
     * @throws SelectorException on an invalid selector
     */
    public static function all(ParentNode $context, string $selector): array
    {
        try {
            $list = $context->querySelectorAll($selector);
        } catch (\DOMException $e) {
            throw new SelectorException($selector, $e);
        }

        // querySelectorAll() only ever yields elements
        return iterator_to_array($list, false);
    }
}
