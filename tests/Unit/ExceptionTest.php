<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Exception\FileNotFoundException;
use HtmlPlucker\Exception\HtmlPluckerException;
use HtmlPlucker\Exception\LoadException;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\ParseException;
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
}
