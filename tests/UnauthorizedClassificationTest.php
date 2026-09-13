<?php

namespace Twitch\Http\Tests;

use PHPUnit\Framework\TestCase;
use React\Http\Message\Response;
use Twitch\Http\Exceptions\HttpException;
use Twitch\Http\Exceptions\InvalidTokenException;
use Twitch\Http\Exceptions\MissingScopeException;
use Twitch\Http\Exceptions\NotFoundException;

/**
 * Twitch overloads 401 for three unrelated conditions and only one of them
 * is worth re-issuing a token over. Getting this wrong is expensive: the
 * client refreshes on every scope failure, burning a refresh-token rotation
 * per call for a retry that cannot succeed.
 */
final class UnauthorizedClassificationTest extends TestCase
{
    private static function unauthorized(string $message): HttpException
    {
        return HttpException::fromResponse(new Response(
            401,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'Unauthorized', 'status' => 401, 'message' => $message]),
        ));
    }

    public function testMissingScopeIsNotTreatedAsAnInvalidToken(): void
    {
        $e = self::unauthorized('Missing scope: bits:read');

        self::assertInstanceOf(MissingScopeException::class, $e);
        self::assertNotInstanceOf(InvalidTokenException::class, $e);
    }

    public function testMissingScopeNamesTheScopeRequired(): void
    {
        self::assertSame(['bits:read'], self::unauthorized('Missing scope: bits:read')->scopes);
    }

    public function testMissingScopeSplitsAlternatives(): void
    {
        $e = self::unauthorized('Missing scope: moderation:read or channel:manage:moderators');

        self::assertSame(['moderation:read', 'channel:manage:moderators'], $e->scopes);
    }

    public function testWrongTokenTypeIsNotRetryable(): void
    {
        // Conduits accept app access tokens only; a user token will never do,
        // so retrying would loop forever.
        $e = self::unauthorized('The API accepts only an app access token.');

        self::assertSame(HttpException::class, $e::class);
        self::assertNotInstanceOf(InvalidTokenException::class, $e);
    }

    public function testGenuinelyInvalidTokenStaysRetryable(): void
    {
        self::assertInstanceOf(InvalidTokenException::class, self::unauthorized('Invalid OAuth token'));
    }

    public function testUnrecognisedUnauthorizedFallsBackToRefreshing(): void
    {
        // If Twitch rewords an expiry message we would rather attempt a
        // pointless refresh than fail to recover from a real expiry.
        self::assertInstanceOf(InvalidTokenException::class, self::unauthorized('Some new wording'));
    }

    public function testOtherStatusesAreUnaffected(): void
    {
        $e = HttpException::fromResponse(new Response(
            404,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'Not Found', 'status' => 404, 'message' => 'segments were not found']),
        ));

        self::assertInstanceOf(NotFoundException::class, $e);
    }
}
