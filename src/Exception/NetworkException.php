<?php declare(strict_types=1);

namespace HtmlPlucker\Exception;

/**
 * Thrown when a URL cannot be reached (timeout, DNS failure, HTTP error, etc.).
 */
class NetworkException extends LoadException {}
