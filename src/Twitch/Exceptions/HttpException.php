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
            401     => InvalidTokenException::class,
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
}
