<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Document;
use HtmlPlucker\Exception\FileNotFoundException;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Exception\NodeNotFoundException;
use HtmlPlucker\Exception\ParseException;
use HtmlPlucker\Node;
use HtmlPlucker\Tests\Support\MockHttpClient;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[RequiresPhp('8.4')]
final class DocumentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // fromString
    // -------------------------------------------------------------------------

    public function test_from_string_returns_document(): void
    {
        $doc = Document::fromString('<p>Hello</p>');
        $this->assertInstanceOf(Document::class, $doc);
    }

    public function test_from_string_throws_on_empty_input(): void
    {
        $this->expectException(ParseException::class);
        Document::fromString('');
    }

    public function test_from_string_throws_on_whitespace_only(): void
    {
        $this->expectException(ParseException::class);
        Document::fromString('   ');
    }

    // -------------------------------------------------------------------------
    // fromUrl
    // -------------------------------------------------------------------------

    public function test_from_url_returns_document(): void
    {
        $mock = (new MockHttpClient())
            ->addResponse('https://example.com', '<h1>Hello</h1>');

        $doc = Document::fromUrl('https://example.com', client: $mock);

        $this->assertInstanceOf(Document::class, $doc);
    }

    public function test_from_url_passes_url_to_client(): void
    {
        $mock = (new MockHttpClient())
            ->addResponse('https://example.com', '<p>ok</p>');

        Document::fromUrl('https://example.com', client: $mock);

        $this->assertTrue($mock->wasRequested('https://example.com'));
    }

    public function test_from_url_passes_timeout_to_client(): void
    {
        $mock = (new MockHttpClient())
            ->addDefaultResponse('<p>ok</p>');

        Document::fromUrl('https://example.com', timeout: 30, client: $mock);

        $this->assertSame(30, $mock->getRequests()[0]['timeout']);
    }

    public function test_from_url_passes_headers_to_client(): void
    {
        $mock = (new MockHttpClient())
            ->addDefaultResponse('<p>ok</p>');

        Document::fromUrl(
            'https://example.com',
            headers: ['X-Token' => 'abc'],
            client: $mock
        );

        $this->assertSame('abc', $mock->getRequests()[0]['headers']['X-Token']);
    }

    public function test_from_url_throws_network_exception_on_failure(): void
    {
        $mock = new MockHttpClient(); // no responses registered

        $this->expectException(NetworkException::class);
        Document::fromUrl('https://unreachable.example', client: $mock);
    }

    public function test_from_url_throws_network_exception_on_http_error(): void
    {
        $mock = (new MockHttpClient())
            ->addResponse('https://example.com', '', 404);

        $this->expectException(NetworkException::class);
        Document::fromUrl('https://example.com', client: $mock);
    }

    public function test_from_url_parses_response_html(): void
    {
        $mock = (new MockHttpClient())
            ->addResponse('https://example.com', '<h1>Title</h1><p>Body</p>');

        $doc = Document::fromUrl('https://example.com', client: $mock);

        $this->assertSame('Title', $doc->expect('h1')->text());
        $this->assertSame('Body', $doc->expect('p')->text());
    }

    // -------------------------------------------------------------------------
    // fromFile
    // -------------------------------------------------------------------------

    public function test_from_file_returns_document(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'htmlplucker_') . '.html';
        file_put_contents($path, '<p>Hello from file</p>');

        $doc = Document::fromFile($path);
        $this->assertInstanceOf(Document::class, $doc);

        unlink($path);
    }

    public function test_from_file_throws_on_missing_file(): void
    {
        $this->expectException(FileNotFoundException::class);
        Document::fromFile('/tmp/this_file_does_not_exist_htmlplucker.html');
    }

    // -------------------------------------------------------------------------
    // first()
    // -------------------------------------------------------------------------

    public function test_first_returns_node_when_found(): void
    {
        $doc = Document::fromString('<div><p class="intro">Hello</p></div>');
        $node = $doc->first('p.intro');
        $this->assertInstanceOf(Node::class, $node);
    }

    public function test_first_returns_null_when_not_found(): void
    {
        $doc = Document::fromString('<div><p>Hello</p></div>');
        $this->assertNull($doc->first('span.missing'));
    }

    // -------------------------------------------------------------------------
    // all()
    // -------------------------------------------------------------------------

    public function test_all_returns_all_matching_nodes(): void
    {
        $doc   = Document::fromString('<ul><li>A</li><li>B</li><li>C</li></ul>');
        $items = $doc->all('li');
        $this->assertCount(3, $items);
    }

    public function test_all_returns_empty_array_when_none_found(): void
    {
        $doc = Document::fromString('<div><p>Hello</p></div>');
        $this->assertSame([], $doc->all('span'));
    }

    public function test_all_returns_array_of_nodes(): void
    {
        $doc   = Document::fromString('<p>A</p><p>B</p>');
        $nodes = $doc->all('p');
        $this->assertContainsOnlyInstancesOf(Node::class, $nodes);
    }

    // -------------------------------------------------------------------------
    // has()
    // -------------------------------------------------------------------------

    public function test_has_returns_true_when_found(): void
    {
        $doc = Document::fromString('<div class="error">Oops</div>');
        $this->assertTrue($doc->has('div.error'));
    }

    public function test_has_returns_false_when_not_found(): void
    {
        $doc = Document::fromString('<div>OK</div>');
        $this->assertFalse($doc->has('div.error'));
    }

    // -------------------------------------------------------------------------
    // expect()
    // -------------------------------------------------------------------------

    public function test_expect_returns_node_when_found(): void
    {
        $doc  = Document::fromString('<h1>Title</h1>');
        $node = $doc->expect('h1');
        $this->assertInstanceOf(Node::class, $node);
    }

    public function test_expect_throws_when_not_found(): void
    {
        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessageMatches('/h2/');

        Document::fromString('<h1>Title</h1>')->expect('h2');
    }
}
