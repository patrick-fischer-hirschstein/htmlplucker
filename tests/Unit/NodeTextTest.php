<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Document;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[RequiresPhp('8.4')]
final class NodeTextTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function textProvider(): array
    {
        return [
            'collapses whitespace' => [
                "<p>\n    Hello\n\t   World  \n</p>", 'p', 'Hello World',
            ],
            'nbsp becomes space' => [
                '<p>1.&nbsp;FC&nbsp;Köln</p>', 'p', '1. FC Köln',
            ],
            'minified table cells are separated' => [
                '<table><tr><td>1</td><td>FC Example</td><td>30</td></tr></table>', 'tr', '1 FC Example 30',
            ],
            'paragraphs are separated' => [
                '<div><p>A</p><p>B</p></div>', 'div', 'A B',
            ],
            'list items are separated' => [
                '<ul><li>A</li><li>B</li></ul>', 'ul', 'A B',
            ],
            'br separates words' => [
                '<p>A<br>B</p>', 'p', 'A B',
            ],
            'inline elements stay joined' => [
                '<p><b>Hel</b>lo <i>World</i></p>', 'p', 'Hello World',
            ],
            'script and style are ignored' => [
                '<div>A <script>var x = 1;</script><style>.a{}</style>B</div>', 'div', 'A B',
            ],
            'comments are ignored' => [
                '<p>A<!-- comment -->B</p>', 'p', 'AB',
            ],
            'empty element yields empty string' => [
                '<div>   </div>', 'div', '',
            ],
        ];
    }

    #[DataProvider('textProvider')]
    public function test_text(string $html, string $selector, string $expected): void
    {
        $this->assertSame(
            $expected,
            Document::fromString($html)->expect($selector)->text()
        );
    }

    public function test_raw_text_keeps_textcontent_unchanged(): void
    {
        $row = Document::fromString(
            '<table><tr><td>1</td><td>FC</td></tr></table>'
        )->expect('tr');

        $this->assertSame('1FC', $row->rawText());
    }

    public function test_raw_text_keeps_whitespace(): void
    {
        $p = Document::fromString("<p>\n  A\n</p>")->expect('p');

        $this->assertSame("\n  A\n", $p->rawText());
    }
}
