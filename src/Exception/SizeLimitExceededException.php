<?php declare(strict_types=1);

namespace HtmlPlucker\Exception;

/**
 * Thrown when a document is larger than the configured size limit
 * (GuzzleHttpClient $maxBytes, Document::fromFile() $maxBytes).
 *
 * $actualSize is the total size if it was known up front (Content-Length
 * header or file size), or null if the limit was hit while reading.
 */
class SizeLimitExceededException extends LoadException
{
    public function __construct(
        string                  $source,
        public readonly int     $limit,
        public readonly ?int    $actualSize = null,
        ?\Throwable             $previous = null,
    ) {
        $size = $actualSize !== null
            ? "{$actualSize} bytes"
            : 'limit reached while reading';

        parent::__construct(
            "\"{$source}\" exceeds the size limit of {$limit} bytes ({$size})",
            0,
            $previous
        );
    }
}
