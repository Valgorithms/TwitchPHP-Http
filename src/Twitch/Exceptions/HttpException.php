<?php

/*
 * This file is a part of the TwitchPHP-Http project.
 *
 * Copyright (c) 2025-present Valithor Obsidion <valithor@valgorithms.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Twitch\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Base class for every error surfaced by {@see \Twitch\Http\Http}.
 *
 * Carries the HTTP status, the decoded Twitch error body (`{status, error,
 * message}`), and the raw response when one is available.
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $error = null,
        public readonly ?ResponseInterface $response = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /**
     * Builds the right exception subclass for a failed response.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $error = is_array($decoded) ? ($decoded['error'] ?? null) : null;
        $message = is_array($decoded) && isset($decoded['message']) && $decoded['message'] !== ''
            ? (string) $decoded['message']
            : ($response->getReasonPhrase() ?: 'Request failed');

        $class = match ($status) {
            400     => BadRequestException::class,
            401     => self::classifyUnauthorized($message),
            403     => NoPermissionsException::class,
            404     => NotFoundException::class,
            405     => MethodNotAllowedException::class,
            409     => ConflictException::class,
            422     => UnprocessableException::class,
            429     => RateLimitException::class,
            default => $status >= 500 ? ServerException::class : self::class,
        };

        return new $class("HTTP {$status}: {$message}", $status, $error, $response);
    }

    /**
     * Twitch overloads 401 for three unrelated conditions, and only one of
     * them is worth re-issuing a token over:
     *
     *  - the grant is too narrow   → {@see MissingScopeException} (re-authorize)
     *  - the wrong *kind* of token → plain {@see HttpException}   (unrecoverable)
     *  - anything else             → {@see InvalidTokenException} (refresh)
     *
     * Unrecognised messages fall through to {@see InvalidTokenException} so a
     * genuine expiry still triggers a refresh even if Twitch reworks the
     * wording. The two carve-outs are the cases where retrying provably
     * cannot help — a scope 401 survives any number of refreshes, and an
     * endpoint that demands an app access token will never accept a user one.
     *
     * @return class-string<self>
     */
    private static function classifyUnauthorized(string $message): string
    {
        if (stripos($message, 'missing scope') !== false) {
            return MissingScopeException::class;
        }

        // e.g. "The API accepts only an app access token." — a different
        // credential is required, not a fresher one.
        if (preg_match('/accepts only an? (app|user) access token/i', $message)) {
            return self::class;
        }

        return InvalidTokenException::class;
    }
}
