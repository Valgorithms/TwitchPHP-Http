<?php

/*
 * This file is a part of the TwitchPHP-Http project.
 *
 * Copyright (c) 2025-present Valithor Obsidion <valithor@valgorithms.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Twitch\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * A snapshot of the Twitch rate-limit bucket, read from the `Ratelimit-*`
 * response headers.
 *
 * Twitch uses a single per-Client-ID points bucket for the Helix API (the
 * default is 800 points per minute; most calls cost one point). The response
 * always reports the bucket ceiling, the points left, and the UNIX time the
 * bucket refills — there are no per-route buckets like Discord's.
 *
 * @link https://dev.twitch.tv/docs/api/guide/#twitch-rate-limits
 */
final class RateLimit
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $resetAt,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): ?self
    {
        if (! $response->hasHeader('Ratelimit-Limit')) {
            return null;
        }

        return new self(
            (int) $response->getHeaderLine('Ratelimit-Limit'),
            (int) $response->getHeaderLine('Ratelimit-Remaining'),
            (int) $response->getHeaderLine('Ratelimit-Reset'),
        );
    }

    /** Whether the bucket is empty and callers should hold until {@see resetAt}. */
    public function isExhausted(): bool
    {
        return $this->remaining <= 0;
    }

    /** Seconds to wait before the bucket refills (never negative). */
    public function retryAfter(): float
    {
        return max(0.0, $this->resetAt - microtime(true));
    }

    public function __toString(): string
    {
        return "{$this->remaining}/{$this->limit} left, resets in " . round($this->retryAfter(), 1) . 's';
    }
}
