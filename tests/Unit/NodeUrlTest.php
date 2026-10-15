<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use HtmlPlucker\Document;
use HtmlPlucker\Http\GuzzleHttpClient;
use HtmlPlucker\Testing\MockHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[RequiresPhp('8.4')]
final class NodeUrlTest extends TestCase
{
    private const string BASE = 'https://example.com/verein/kader';

    /**
     * @return array<string, array{string, ?string, string, ?string}>
     *         [html, baseUrl, attribute, expected] — the tested element is #x
     */
    public static function urlProvider(): array
    {
        return [
            // Missing / empty
            'missing attribute'      => ['<a id="x">t</a>', self::BASE, 'href', null],
            'empty attribute'        => ['<a id="x" href="">t</a>', self::BASE, 'href', null],
            'whitespace only'        => ['<a id="x" href="   ">t</a>', self::BASE, 'href', null],

            // Resolution against $baseUrl
            'parent path'            => ['<a id="x" href="../spiel/123">t</a>', self::BASE, 'href', 'https://example.com/spiel/123'],
            'absolute path'          => ['<a id="x" href="/tabelle">t</a>', self::BASE, 'href', 'https://example.com/tabelle'],
            'query only'             => ['<a id="x" href="?saison=2026">t</a>', self::BASE, 'href', 'https://example.com/verein/kader?saison=2026'],
            'protocol relative'      => ['<a id="x" href="//cdn.example.org/l.png">t</a>', self::BASE, 'href', 'https://cdn.example.org/l.png'],
            'value is trimmed'       => ['<a id="x" href="  /tabelle ">t</a>', self::BASE, 'href', 'https://example.com/tabelle'],
            'other attribute (src)'  => ['<img id="x" src="logo.png">', self::BASE, 'src', 'https://example.com/verein/logo.png'],
            'already absolute'       => ['<a id="x" href="https://other.org/a">t</a>', self::BASE, 'href', 'https://other.org/a'],
            'mailto unchanged'       => ['<a id="x" href="mailto:info@example.com">t</a>', self::BASE, 'href', 'mailto:info@example.com'],

            // <base href>
            'relative base element'  => ['<base href="/archiv/"><a id="x" href="bericht.html">t</a>', self::BASE, 'href', 'https://example.com/archiv/bericht.html'],
            'absolute base element'  => ['<base href="https://other.org/x/"><a id="x" href="y">t</a>', self::BASE, 'href', 'https://other.org/x/y'],
            'base element only'      => ['<base href="https://other.org/x/"><a id="x" href="y">t</a>', null, 'href', 'https://other.org/x/y'],
            'base without href'      => ['<base target="_blank"><a id="x" href="y">t</a>', self::BASE, 'href', 'https://example.com/verein/y'],

            // No usable absolute base -> unchanged
            'no base at all'         => ['<a id="x" href="/tabelle">t</a>', null, 'href', '/tabelle'],
            'relative base only'     => ['<base href="/archiv/"><a id="x" href="b.html">t</a>', null, 'href', 'b.html'],
            'relative $baseUrl'      => ['<a id="x" href="b.html">t</a>', '/archiv/', 'href', 'b.html'],
            'malformed value'        => ['<a id="x" href="http://[::1">t</a>', self::BASE, 'href', 'http://[::1'],
        ];
    }

    #[DataProvider('urlProvider')]
    public function test_url(string $html, ?string $baseUrl, string $attribute, ?string $expected): void
    {
        $node = Document::fromString($html, baseUrl: $baseUrl)->expect('#x');

        $this->assertSame($expected, $node->url($attribute));
    }

    public function test_from_file_with_base_url(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'htmlplucker_');
        file_put_contents($path, '<a href="../spiel/7">t</a>');

        try {
            $doc = Document::fromFile($path, baseUrl: self::BASE);
            $this->assertSame('https://example.com/spiel/7', $doc->expect('a')->url());
        } finally {
            unlink($path);
        }
    }

    public function test_from_url_uses_requested_url_as_base(): void
    {
        $mock = (new MockHttpClient())
            ->addResponse('https://example.com/verein/', '<a href="kader">t</a>');

        $doc = Document::fromUrl('https://example.com/verein/', client: $mock);

        $this->assertSame('https://example.com/verein/kader', $doc->expect('a')->url());
    }

    public function test_from_url_uses_final_url_after_redirect_as_base(): void
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(301, ['Location' => 'https://example.com/neu/']),
            new Response(200, [], '<a href="seite">t</a>'),
        ]));
        $client = new GuzzleHttpClient(new Client(['handler' => $stack]));

        $doc = Document::fromUrl('https://example.com/alt/', client: $client);

        $this->assertSame('https://example.com/neu/seite', $doc->expect('a')->url());
    }
}
