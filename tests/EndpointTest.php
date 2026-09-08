<?php

namespace Twitch\Http\Tests;

use PHPUnit\Framework\TestCase;
use Twitch\Http\Endpoint;

final class EndpointTest extends TestCase
{
    public function testPlainEndpointRendersAsIs(): void
    {
        self::assertSame('users', (string) new Endpoint(Endpoint::USERS));
        self::assertSame('users', (string) new Endpoint('/users'));
    }

    public function testQueryParamsAreAppendedAndEncoded(): void
    {
        $endpoint = (new Endpoint(Endpoint::STREAMS))
            ->addQuery('user_login', 'twitch dev')
            ->addQuery('first', 20);

        self::assertSame('streams?user_login=twitch%20dev&first=20', (string) $endpoint);
    }

    public function testArrayQueryParamsAreRepeated(): void
    {
        $endpoint = (new Endpoint(Endpoint::USERS))->addQuery('id', ['1', '2', '3']);

        self::assertSame('users?id=1&id=2&id=3', (string) $endpoint);
    }

    public function testWithQuerySkipsNulls(): void
    {
        $endpoint = (new Endpoint(Endpoint::CLIPS))->withQuery([
            'broadcaster_id' => '123',
            'game_id' => null,
            'is_featured' => true,
        ]);

        self::assertSame('clips?broadcaster_id=123&is_featured=true', (string) $endpoint);
    }

    public function testPathParamsBind(): void
    {
        $bound = Endpoint::bind('extensions/:extension_id/configurations', 'abc123');

        self::assertSame('extensions/abc123/configurations', (string) $bound);
    }

    public function testMajorParameterRenderSkipsQuery(): void
    {
        $endpoint = (new Endpoint(Endpoint::STREAMS))->addQuery('first', 5);

        self::assertSame('streams', $endpoint->toAbsoluteEndpoint(true));
        self::assertSame('streams?first=5', $endpoint->toAbsoluteEndpoint(false));
    }
}
