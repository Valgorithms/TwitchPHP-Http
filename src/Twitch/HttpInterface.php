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

use React\Promise\PromiseInterface;

/**
 * The contract {@see Http} fulfils. Depend on this rather than the concrete
 * class so the transport can be decorated or faked (auth-refresh wrappers,
 * test doubles, alternative drivers).
 *
 * Every method resolves with the decoded JSON body — Twitch's
 * `{ "data": [...], "pagination": {...} }` envelope untouched — or `null` for a
 * `204`, and rejects with a {@see Exceptions\HttpException} subclass otherwise.
 */
interface HttpInterface
{
    /** @param array<string, string> $headers */
    public function get(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface;

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function post(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface;

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function put(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface;

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function patch(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface;

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function delete(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface;

    /**
     * @param array<string, mixed>|null $content
     * @param array<string, string>     $headers
     */
    public function request(string $method, Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface;

    /** Swap in a fresh OAuth token (e.g. after a refresh). */
    public function setToken(string $token): void;

    /** The most recent rate-limit bucket snapshot, if any request has completed. */
    public function getRateLimit(): ?RateLimit;
}
