<?php declare(strict_types=1);

namespace HtmlPlucker\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use HtmlPlucker\Exception\NetworkException;
use HtmlPlucker\Http\GuzzleHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class GuzzleHttpClientTest extends TestCase
{
    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    /**
     * @param Response|\Throwable ...$queue responses/exceptions in order;
     *                                      defaults to a single 200 "ok"
     */
    private function client(Response|\Throwable ...$queue): GuzzleHttpClient
    {
        $queue = $queue ?: [new Response(200, [], 'ok')];
        $stack = HandlerStack::create(new MockHandler($queue));
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

    public function test_returns_response_body(): void
    {
        $body = $this->client(new Response(200, [], '<h1>Hi</h1>'))
            ->get('https://example.com');

        $this->assertSame('<h1>Hi</h1>', $body);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function httpErrorProvider(): array
    {
        return [
            '404 Not Found'           => [404],
            '429 Too Many Requests'   => [429],
            '503 Service Unavailable' => [503],
        ];
    }

    #[DataProvider('httpErrorProvider')]
    public function test_http_error_carries_status_code(int $status): void
    {
        try {
            $this->client(new Response($status))->get('https://example.com');
            $this->fail('NetworkException erwartet');
        } catch (NetworkException $e) {
            $this->assertSame($status, $e->statusCode);
            $this->assertInstanceOf(RequestException::class, $e->getPrevious());
        }
    }

    public function test_connection_error_has_no_status_code(): void
    {
        $failure = new ConnectException(
            'Connection refused',
            new Request('GET', 'https://example.com')
        );

        try {
            $this->client($failure)->get('https://example.com');
            $this->fail('NetworkException erwartet');
        } catch (NetworkException $e) {
            $this->assertNull($e->statusCode);
            $this->assertSame($failure, $e->getPrevious());
        }
    }
}
