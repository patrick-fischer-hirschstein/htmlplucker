<?php declare(strict_types=1);

namespace HtmlPlucker\Exception;

/**
 * Thrown when a CSS selector cannot be parsed (e.g. "div[").
 */
class SelectorException extends HtmlPluckerException
{
    public function __construct(string $selector, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Invalid CSS selector: \"{$selector}\"",
            0,
            $previous
        );
    }
}
