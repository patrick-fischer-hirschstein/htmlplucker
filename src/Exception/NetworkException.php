<?php declare(strict_types=1);

namespace HtmlPlucker\Exception;

/**
 * Thrown when a URL cannot be reached (timeout, DNS failure, HTTP error, etc.).
 *
 * $statusCode holds the HTTP status for 4xx/5xx responses
 * and is null when no response was received at all.
 */
class NetworkException extends LoadException
{
    public function __construct(
        string                  $message = '',
        public readonly ?int    $statusCode = null,
        ?\Throwable             $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
