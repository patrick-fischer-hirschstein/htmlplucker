<?php declare(strict_types=1);

namespace HtmlPlucker\Exception;

/**
 * Thrown when a URL cannot be reached (timeout, DNS failure, HTTP error, etc.).
 *
 * $statusCode holds the HTTP status of 4xx/5xx error responses.
 * It is null for all other failures: no response at all (DNS, connect,
 * timeout) or a transfer failure after a non-error response.
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
