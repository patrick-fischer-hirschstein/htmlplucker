<?php declare(strict_types=1);

namespace HtmlPlucker;

/**
 * Default limits shared by the HTTP client and file loading.
 */
final class Limits
{
    /**
     * Default maximum document size: 10 MiB.
     *
     * Used by GuzzleHttpClient and Document::fromFile() unless another
     * limit is passed; pass null there to disable the limit.
     */
    public const int DEFAULT_MAX_BYTES = 10 * 1024 * 1024;

    private function __construct() {}
}
