<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use HtmlPlucker\Http\GuzzleHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class GuzzleHttpClientTest extends TestCase
{
    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    private function client(): GuzzleHttpClient
    {
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], 'ok')]));
        $stack->push(Middleware::history($this->history));

        return new GuzzleHttpClient(new Client(['handler' => $stack]));
    }

    private function sentRequest(): RequestInterface
    {
        return $this->history[0]['request'];
    }

    public function test_sends_default_user_agent(): void
    {
        $this->client()->get('https://example.com');

        $this->assertSame(
            ['HtmlPlucker/1.0'],
            $this->sentRequest()->getHeader('User-Agent')
        );
    }

    public function test_custom_user_agent_replaces_default_case_insensitively(): void
    {
        $this->client()->get('https://example.com', headers: ['user-agent' => 'MyBot/2.0']);

        $this->assertSame(
            ['MyBot/2.0'],
            $this->sentRequest()->getHeader('User-Agent')
        );
    }

    public function test_sends_additional_headers_alongside_default(): void
    {
        $this->client()->get('https://example.com', headers: ['X-Token' => 'abc']);

        $request = $this->sentRequest();
        $this->assertSame(['abc'], $request->getHeader('X-Token'));
        $this->assertSame(['HtmlPlucker/1.0'], $request->getHeader('User-Agent'));
    }
}
