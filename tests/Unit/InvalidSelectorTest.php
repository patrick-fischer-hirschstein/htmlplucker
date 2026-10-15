<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Document;
use HtmlPlucker\Exception\SelectorException;
use HtmlPlucker\Node;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[RequiresPhp('8.4')]
final class InvalidSelectorTest extends TestCase
{
    private const string INVALID = 'div[';
    private const string HTML    = '<div><p>Text</p></div>';

    /**
     * @return array<string, array{string}>
     */
    public static function methodProvider(): array
    {
        return [
            'first'  => ['first'],
            'all'    => ['all'],
            'has'    => ['has'],
            'expect' => ['expect'],
        ];
    }

    #[DataProvider('methodProvider')]
    public function test_document_throws_selector_exception(string $method): void
    {
        $this->assertSelectorException(
            Document::fromString(self::HTML),
            $method
        );
    }

    #[DataProvider('methodProvider')]
    public function test_node_throws_selector_exception(string $method): void
    {
        $this->assertSelectorException(
            Document::fromString(self::HTML)->expect('div'),
            $method
        );
    }

    public function test_message_contains_selector(): void
    {
        $this->expectException(SelectorException::class);
        $this->expectExceptionMessage('div[');

        Document::fromString(self::HTML)->first(self::INVALID);
    }

    private function assertSelectorException(Document|Node $target, string $method): void
    {
        try {
            $target->{$method}(self::INVALID);
            $this->fail("{$method}() hat keine SelectorException geworfen");
        } catch (SelectorException $e) {
            $this->assertInstanceOf(\DOMException::class, $e->getPrevious());
        }
    }
}
