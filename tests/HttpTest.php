<?php

namespace Twitch\Http\Tests;

use PHPUnit\Framework\TestCase;

use function React\Async\await;

use React\EventLoop\Loop;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

use Twitch\Http\DriverInterface;
use Twitch\Http\Endpoint;
use Twitch\Http\Exceptions\InvalidTokenException;
use Twitch\Http\Exceptions\NotFoundException;
use Twitch\Http\Http;
use Twitch\Http\Request;

/**
 * A driver that replays a scripted list of responses and records the requests.
 */
final class FakeDriver implements DriverInterface
{
    /** @var list<Response> */
    public array $script = [];

    /** @var list<Request> */
    public array $seen = [];

    public function runRequest(Request $request): PromiseInterface
    {
        $this->seen[] = $request;

        return resolve(array_shift($this->script) ?? new Response(200, [], '{"data":[]}'));
    }
}

final class HttpTest extends TestCase
{
    private function http(FakeDriver $driver): Http
    {
        $http = new Http('tok', 'client-123', Loop::get());
        $http->setDriver($driver);

        return $http;
    }

    public function testSuccessResolvesWithDecodedEnvelopeAndSendsAuthHeaders(): void
    {
        $driver = new FakeDriver();
        $driver->script[] = new Response(200, [], '{"data":[{"id":"1"}],"pagination":{"cursor":"x"}}');

        $body = await($this->http($driver)->get((new Endpoint(Endpoint::USERS))->addQuery('id', '1')));

        self::assertSame([['id' => '1']], $body['data']);
        self::assertSame('x', $body['pagination']['cursor']);

        $headers = $driver->seen[0]->getHeaders();
        self::assertSame('Bearer tok', $headers['Authorization']);
        self::assertSame('client-123', $headers['Client-Id']);
    }

    public function test204ResolvesNull(): void
    {
        $driver = new FakeDriver();
        $driver->script[] = new Response(204, [], '');

        self::assertNull(await($this->http($driver)->delete(Endpoint::BANS)));
    }

    public function test404RejectsWithTypedException(): void
    {
        $driver = new FakeDriver();
        $driver->script[] = new Response(404, [], '{"error":"Not Found","status":404,"message":"user not found"}');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('user not found');
        await($this->http($driver)->get(Endpoint::USERS));
    }

    public function test401RejectsAsInvalidToken(): void
    {
        $driver = new FakeDriver();
        $driver->script[] = new Response(401, [], '{"error":"Unauthorized","status":401,"message":"invalid oauth token"}');

        $this->expectException(InvalidTokenException::class);
        await($this->http($driver)->get(Endpoint::USERS));
    }

    public function testRetriesOnServerErrorThenSucceeds(): void
    {
        $driver = new FakeDriver();
        $driver->script[] = new Response(503, [], 'unavailable');
        $driver->script[] = new Response(200, [], '{"data":[{"ok":true}]}');

        $body = await($this->http($driver)->get(Endpoint::STREAMS));

        self::assertSame([['ok' => true]], $body['data']);
        self::assertCount(2, $driver->seen);
    }

    public function test429HoldsThenRetries(): void
    {
        $driver = new FakeDriver();
        $reset = time() + 1;
        $driver->script[] = new Response(429, ['Ratelimit-Limit' => '800', 'Ratelimit-Remaining' => '0', 'Ratelimit-Reset' => (string) $reset], '{"error":"Too Many Requests","status":429}');
        $driver->script[] = new Response(200, [], '{"data":[]}');

        $body = await($this->http($driver)->get(Endpoint::GAMES));

        self::assertSame([], $body['data']);
        self::assertCount(2, $driver->seen);
    }
}
