<?php declare(strict_types=1);

namespace HtmlPlucker\Internal;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use HtmlPlucker\Exception\SizeLimitExceededException;
use Psr\Http\Message\StreamInterface;

/**
 * Response sink that refuses to grow beyond $maxBytes.
 *
 * Guzzle writes the (already decoded) response body into the sink;
 * the first write that would exceed the limit throws, which aborts the
 * transfer. Buffers in php://temp (memory, spilling to disk above 2 MB).
 *
 * @internal Not part of the public API — may change without notice.
 */
final class LimitedSink implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;

    private int $written = 0;

    public function __construct(
        private readonly string $source,
        private readonly int    $maxBytes,
    ) {
        $this->stream = Utils::streamFor(Utils::tryFopen('php://temp', 'w+'));
    }

    /**
     * @throws SizeLimitExceededException before writing past the limit
     */
    public function write(string $string): int
    {
        $this->written += \strlen($string);

        if ($this->written > $this->maxBytes) {
            throw new SizeLimitExceededException($this->source, $this->maxBytes);
        }

        return $this->stream->write($string);
    }
}
