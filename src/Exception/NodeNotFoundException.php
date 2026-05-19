<?php declare(strict_types=1);

namespace HtmlPlucker\Exception;

/**
 * Thrown by Document::expect() and Node::expect() when no element
 * matches the given CSS selector.
 */
class NodeNotFoundException extends HtmlPluckerException
{
    public function __construct(string $selector)
    {
        parent::__construct(
            "No element found matching selector: \"{$selector}\""
        );
    }
}
