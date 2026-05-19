<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Document;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Node;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[RequiresPhp('8.4')]
final class NodeTest extends TestCase
{
    private function doc(string $html): Document
    {
        return Document::fromString($html);
    }

    // -------------------------------------------------------------------------
    // Content
    // -------------------------------------------------------------------------

    public function test_text_returns_plaintext(): void
    {
        $node = $this->doc('<p>Hello <b>World</b></p>')->expect('p');
        $this->assertSame('Hello World', $node->text());
    }

    public function test_text_strips_tags(): void
    {
        $node = $this->doc('<div><p>foo</p><p>bar</p></div>')->expect('div');
        $this->assertStringNotContainsString('<p>', $node->text());
    }

    public function test_html_returns_inner_html(): void
    {
        $node = $this->doc('<div><b>bold</b></div>')->expect('div');
        $this->assertStringContainsString('<b>bold</b>', $node->html());
    }

    public function test_tag_returns_lowercase(): void
    {
        $node = $this->doc('<DIV>test</DIV>')->expect('div');
        $this->assertSame('div', $node->tag());
    }

    // -------------------------------------------------------------------------
    // Attributes
    // -------------------------------------------------------------------------

    public function test_attr_returns_value(): void
    {
        $node = $this->doc('<a href="https://example.com">link</a>')->expect('a');
        $this->assertSame('https://example.com', $node->attr('href'));
    }

    public function test_attr_returns_null_when_missing(): void
    {
        $node = $this->doc('<a href="#">link</a>')->expect('a');
        $this->assertNull($node->attr('data-missing'));
    }

    public function test_attr_returns_default_when_missing(): void
    {
        $node = $this->doc('<a href="#">link</a>')->expect('a');
        $this->assertSame('fallback', $node->attr('data-missing', 'fallback'));
    }

    public function test_attrs_returns_all_attributes(): void
    {
        $node  = $this->doc('<input type="text" name="q" required>')->expect('input');
        $attrs = $node->attrs();

        $this->assertArrayHasKey('type', $attrs);
        $this->assertArrayHasKey('name', $attrs);
        $this->assertSame('text', $attrs['type']);
        $this->assertSame('q', $attrs['name']);
    }

    // -------------------------------------------------------------------------
    // Querying within subtree
    // -------------------------------------------------------------------------

    public function test_first_finds_within_subtree(): void
    {
        $div  = $this->doc('<div><span>inner</span></div><span>outer</span>')->expect('div');
        $span = $div->first('span');

        $this->assertNotNull($span);
        $this->assertSame('inner', $span->text());
    }

    public function test_first_returns_null_on_empty_element(): void
    {
        $div = $this->doc('<div></div>')->expect('div');
        $this->assertNull($div->first('span'));
    }

    public function test_all_finds_only_within_subtree(): void
    {
        $div  = $this->doc('<div><p>A</p><p>B</p></div><p>C</p>')->expect('div');
        $paras = $div->all('p');
        $this->assertCount(2, $paras);
    }

    public function test_has_returns_true_when_child_exists(): void
    {
        $div = $this->doc('<div><span>yes</span></div>')->expect('div');
        $this->assertTrue($div->has('span'));
    }

    public function test_has_returns_false_for_empty_element(): void
    {
        $div = $this->doc('<div></div>')->expect('div');
        $this->assertFalse($div->has('span'));
    }

    public function test_expect_throws_with_selector_in_message(): void
    {
        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessageMatches('/span\.missing/');

        $this->doc('<div></div>')->expect('div')->expect('span.missing');
    }

    // -------------------------------------------------------------------------
    // Traversal
    // -------------------------------------------------------------------------

    public function test_parent_returns_parent_element(): void
    {
        $span = $this->doc('<div><span>text</span></div>')->expect('span');
        $this->assertSame('div', $span->parent()?->tag());
    }

    public function test_children_returns_direct_children(): void
    {
        $ul       = $this->doc('<ul><li>A</li><li>B</li><li>C</li></ul>')->expect('ul');
        $children = $ul->children();

        $this->assertCount(3, $children);
        $this->assertContainsOnlyInstancesOf(Node::class, $children);
    }

    public function test_next_returns_next_sibling(): void
    {
        $first = $this->doc('<ul><li>A</li><li>B</li></ul>')->all('li')[0];
        $this->assertSame('B', $first->next()?->text());
    }

    public function test_prev_returns_previous_sibling(): void
    {
        $second = $this->doc('<ul><li>A</li><li>B</li></ul>')->all('li')[1];
        $this->assertSame('A', $second->prev()?->text());
    }

    public function test_next_returns_null_for_last_sibling(): void
    {
        $last = $this->doc('<ul><li>A</li><li>B</li></ul>')->all('li')[1];
        $this->assertNull($last->next());
    }

    public function test_prev_returns_null_for_first_sibling(): void
    {
        $first = $this->doc('<ul><li>A</li><li>B</li></ul>')->all('li')[0];
        $this->assertNull($first->prev());
    }
}
