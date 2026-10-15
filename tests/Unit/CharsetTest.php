<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use HtmlPlucker\Document;
use HtmlPlucker\Exception\ParseException;
use HtmlPlucker\Testing\MockHttpClient;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

/**
 * Encoding precedence: BOM > charset (header/parameter) > <meta charset> > UTF-8
 */
#[RequiresPhp('8.4')]
final class CharsetTest extends TestCase
{
    private const string URL = 'https://example.com';

    /** "Köln" encoded as ISO-8859-1 */
    private const string LATIN1_KOELN = "K\xF6ln";

    private function fromUrl(string $body, ?string $charset): Document
    {
        $mock = (new MockHttpClient())->addResponse(self::URL, $body, 200, $charset);

        return Document::fromUrl(self::URL, client: $mock);
    }

    // -------------------------------------------------------------------------
    // fromUrl — charset from HTTP header
    // -------------------------------------------------------------------------

    public function test_header_charset_decodes_latin1_body(): void
    {
        $doc = $this->fromUrl('<p>' . self::LATIN1_KOELN . '</p>', 'ISO-8859-1');

        $this->assertSame('Köln', $doc->expect('p')->text());
    }

    public function test_without_any_charset_latin1_body_is_misdecoded(): void
    {
        // Documents why the header matters: the fallback is UTF-8
        $doc = $this->fromUrl('<p>' . self::LATIN1_KOELN . '</p>', null);

        $this->assertNotSame('Köln', $doc->expect('p')->text());
    }

    public function test_header_charset_takes_precedence_over_meta(): void
    {
        $doc = $this->fromUrl(
            '<meta charset="utf-8"><p>' . self::LATIN1_KOELN . '</p>',
            'ISO-8859-1'
        );

        $this->assertSame('Köln', $doc->expect('p')->text());
    }

    public function test_unknown_header_charset_falls_back_to_meta(): void
    {
        $doc = $this->fromUrl(
            '<meta charset="ISO-8859-1"><p>' . self::LATIN1_KOELN . '</p>',
            'none'
        );

        $this->assertSame('Köln', $doc->expect('p')->text());
    }

    public function test_utf8_bom_takes_precedence_over_header_charset(): void
    {
        $doc = $this->fromUrl("\xEF\xBB\xBF<p>Köln</p>", 'ISO-8859-1');

        // body text must not contain a leftover BOM ("ï»¿") or mojibake ("KÃ¶ln")
        $this->assertSame('Köln', $doc->expect('body')->text());
    }

    public function test_utf16le_bom_takes_precedence_over_charset_parameter(): void
    {
        $html = "\xFF\xFE" . mb_convert_encoding('<p>Köln</p>', 'UTF-16LE', 'UTF-8');

        $doc = Document::fromString($html, 'ISO-8859-1');

        $this->assertSame('Köln', $doc->expect('body')->text());
    }

    // -------------------------------------------------------------------------
    // fromString / fromFile — charset parameter
    // -------------------------------------------------------------------------

    public function test_from_string_with_charset(): void
    {
        $doc = Document::fromString('<p>' . self::LATIN1_KOELN . '</p>', 'ISO-8859-1');

        $this->assertSame('Köln', $doc->expect('p')->text());
    }

    public function test_from_string_with_invalid_charset_throws_parse_exception(): void
    {
        try {
            Document::fromString('<p>x</p>', 'no-such-charset');
            $this->fail('ParseException erwartet');
        } catch (ParseException $e) {
            $this->assertInstanceOf(\ValueError::class, $e->getPrevious());
        }
    }

    public function test_from_file_with_charset(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'htmlplucker_');
        file_put_contents($path, '<p>' . self::LATIN1_KOELN . '</p>');

        try {
            $doc = Document::fromFile($path, 'ISO-8859-1');
            $this->assertSame('Köln', $doc->expect('p')->text());
        } finally {
            unlink($path);
        }
    }
}
