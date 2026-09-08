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

use React\Promise\Deferred;

/**
 * One prepared HTTP call: method, {@see Endpoint}, body, headers, and the
 * {@see Deferred} that {@see Http} settles with the decoded payload.
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 */
final class Request
{
    private int $attempts = 0;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly Deferred $deferred,
        private readonly string $method,
        private readonly Endpoint $endpoint,
        private readonly string $content,
        private array $headers,
    ) {
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getEndpoint(): Endpoint
    {
        return $this->endpoint;
    }

    /** The absolute URL, base + rendered endpoint + query string. */
    public function getUrl(): string
    {
        return Http::BASE_URL . '/' . $this->endpoint->toAbsoluteEndpoint();
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getDeferred(): Deferred
    {
        return $this->deferred;
    }

    public function bumpAttempts(): int
    {
        return ++$this->attempts;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function __toString(): string
    {
        return $this->getMethod() . ' ' . $this->endpoint->toAbsoluteEndpoint();
    }
}
