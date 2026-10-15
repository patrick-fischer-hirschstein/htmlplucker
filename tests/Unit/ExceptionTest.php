<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Exception\FileNotFoundException;
use HtmlPlucker\Exception\HtmlPluckerException;
use HtmlPlucker\Exception\LoadException;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\ParseException;
use HtmlPlucker\Exception\SelectorException;
use HtmlPlucker\Exception\SizeLimitExceededException;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    public function test_hierarchy_load_exception_extends_base(): void
    {
        $this->assertInstanceOf(
            HtmlPluckerException::class,
            new LoadException()
        );
    }

    public function test_hierarchy_network_exception_extends_load(): void
    {
        $e = new NetworkException('Could not fetch URL: "https://x.com"');
        $this->assertInstanceOf(LoadException::class, $e);
        $this->assertInstanceOf(HtmlPluckerException::class, $e);
    }

    public function test_hierarchy_file_not_found_extends_load(): void
    {
        $e = new FileNotFoundException('File not found: "/tmp/x.html"');
        $this->assertInstanceOf(LoadException::class, $e);
    }

    public function test_hierarchy_parse_exception_extends_base(): void
    {
        $this->assertInstanceOf(
            HtmlPluckerException::class,
            new ParseException('empty')
        );
    }

    public function test_node_not_found_message_contains_selector(): void
    {
        $e = new NodeNotFoundException('div.content');
        $this->assertStringContainsString('div.content', $e->getMessage());
    }

    public function test_node_not_found_extends_base(): void
    {
        $this->assertInstanceOf(
            HtmlPluckerException::class,
            new NodeNotFoundException('h1')
        );
    }

    public function test_all_exceptions_extend_runtime_exception(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new HtmlPluckerException());
    }

    public function test_network_exception_status_code_defaults_to_null(): void
    {
        $this->assertNull((new NetworkException('timeout'))->statusCode);
    }

    public function test_network_exception_keeps_status_code_and_previous(): void
    {
        $previous = new \RuntimeException('upstream');
        $e        = new NetworkException('HTTP 503', statusCode: 503, previous: $previous);

        $this->assertSame(503, $e->statusCode);
        $this->assertSame($previous, $e->getPrevious());
    }

    public function test_size_limit_exception_extends_load_exception(): void
    {
        $this->assertInstanceOf(
            LoadException::class,
            new SizeLimitExceededException('https://example.com', 1000)
        );
    }

    public function test_size_limit_exception_keeps_limit_and_actual_size(): void
    {
        $known   = new SizeLimitExceededException('/tmp/big.html', 1000, 5000);
        $unknown = new SizeLimitExceededException('/tmp/big.html', 1000);

        $this->assertSame(1000, $known->limit);
        $this->assertSame(5000, $known->actualSize);
        $this->assertNull($unknown->actualSize);
        $this->assertStringContainsString('/tmp/big.html', $known->getMessage());
        $this->assertStringContainsString('1000', $known->getMessage());
    }

    public function test_selector_exception_extends_base(): void
    {
        $this->assertInstanceOf(
            HtmlPluckerException::class,
            new SelectorException('div[')
        );
    }

    public function test_selector_exception_keeps_selector_and_previous(): void
    {
        $previous = new \DOMException('Syntax error');
        $e        = new SelectorException('div[', $previous);

        $this->assertStringContainsString('div[', $e->getMessage());
        $this->assertSame($previous, $e->getPrevious());
    }
}
