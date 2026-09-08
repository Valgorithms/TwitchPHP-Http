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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

use Twitch\Http\Drivers\React as ReactDriver;
use Twitch\Http\Exceptions\HttpException;
use Twitch\Http\Exceptions\RateLimitException;

/**
 * Non-blocking HTTP client for the Twitch Helix API.
 *
 * Every call gets the `Authorization: Bearer` and `Client-Id` headers Twitch
 * requires, is funnelled through a bounded concurrency queue, and is retried
 * on `429` (honouring `Ratelimit-Reset`) and on transient `5xx`. Successful
 * responses resolve with the decoded JSON body — Twitch's `{data, pagination}`
 * envelope is passed through untouched; a `204` resolves with `null`.
 *
 * @link https://dev.twitch.tv/docs/api/
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 */
final class Http
{
    public const VERSION = '1.0.0';

    /** Helix API base. OAuth lives on a different host — see {@see OAuth}. */
    public const BASE_URL = 'https://api.twitch.tv/helix';

    /** In-flight request ceiling. */
    public const CONCURRENT_REQUESTS = 8;

    /** Give up after this many attempts at one request. */
    public const MAX_ATTEMPTS = 4;

    private ?DriverInterface $driver;

    private LoggerInterface $logger;

    /** @var \SplQueue<Request> */
    private \SplQueue $queue;

    private int $inFlight = 0;

    private ?RateLimit $rateLimit = null;

    /** Set while the client is holding for the bucket to refill. */
    private bool $throttled = false;

    public function __construct(
        private string $token,
        private readonly string $clientId,
        private readonly LoopInterface $loop,
        ?LoggerInterface $logger = null,
        ?DriverInterface $driver = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->driver = $driver;
        $this->queue = new \SplQueue();
    }

    /**
     * Convenience constructor that wires the default ReactPHP driver against the
     * shared event loop.
     *
     * @param array<string, mixed> $socketOptions Forwarded to the socket connector.
     */
    public static function create(
        string $token,
        string $clientId,
        ?LoggerInterface $logger = null,
        ?LoopInterface $loop = null,
        array $socketOptions = [],
    ): self {
        $loop ??= Loop::get();

        return new self($token, $clientId, $loop, $logger, new ReactDriver($loop, $socketOptions));
    }

    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /** Swap in a fresh OAuth token (e.g. after a refresh). Queued requests pick it up. */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getRateLimit(): ?RateLimit
    {
        return $this->rateLimit;
    }

    /** @param array<string, string> $headers */
    public function get(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface
    {
        return $this->request('GET', $endpoint, $content, $headers);
    }

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function post(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface
    {
        return $this->request('POST', $endpoint, $content, $headers);
    }

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function put(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface
    {
        return $this->request('PUT', $endpoint, $content, $headers);
    }

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function patch(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface
    {
        return $this->request('PATCH', $endpoint, $content, $headers);
    }

    /** @param array<string, mixed>|null $content @param array<string, string> $headers */
    public function delete(Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface
    {
        return $this->request('DELETE', $endpoint, $content, $headers);
    }

    /**
     * @param array<string, mixed>|null $content
     * @param array<string, string>     $headers
     */
    public function request(string $method, Endpoint|string $endpoint, ?array $content = null, array $headers = []): PromiseInterface
    {
        if ($this->driver === null) {
            return reject(new HttpException('No HTTP driver configured. Pass one to the constructor or call Http::create().'));
        }

        $endpoint = $endpoint instanceof Endpoint ? $endpoint : new Endpoint($endpoint);

        $body = '';
        $headers += ['Client-Id' => $this->clientId, 'Accept' => 'application/json'];
        if ($content !== null) {
            $body = json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $headers += ['Content-Type' => 'application/json'];
        }

        $deferred = new Deferred();
        $this->queue->enqueue(new Request($deferred, $method, $endpoint, $body, $headers));
        $this->pump();

        return $deferred->promise();
    }

    /** Dispatches queued requests up to the concurrency ceiling. */
    private function pump(): void
    {
        if ($this->throttled) {
            return;
        }

        while ($this->inFlight < self::CONCURRENT_REQUESTS && ! $this->queue->isEmpty()) {
            $this->send($this->queue->dequeue());
        }
    }

    private function send(Request $request): void
    {
        $request->setHeader('Authorization', 'Bearer ' . $this->token);
        $request->setHeader('User-Agent', 'TwitchPHP-Http/' . self::VERSION . ' (+https://github.com/valzargaming/TwitchPHP-Http)');
        $attempt = $request->bumpAttempts();
        ++$this->inFlight;

        $this->logger->debug("→ {$request}" . ($attempt > 1 ? " (attempt {$attempt})" : ''));

        $this->driver->runRequest($request)->then(
            function (ResponseInterface $response) use ($request): void {
                --$this->inFlight;
                $this->rateLimit = RateLimit::fromResponse($response) ?? $this->rateLimit;
                $this->handleResponse($request, $response);
            },
            function (\Throwable $e) use ($request): void {
                --$this->inFlight;
                if ($request->getAttempts() < self::MAX_ATTEMPTS) {
                    $this->logger->warning("transport error on {$request}: {$e->getMessage()} — retrying");
                    $this->retry($request, 1.0 * $request->getAttempts());

                    return;
                }
                $request->getDeferred()->reject(new HttpException("Transport error on {$request}: {$e->getMessage()}", 0, null, null, $e));
                $this->pump();
            },
        );
    }

    private function handleResponse(Request $request, ResponseInterface $response): void
    {
        $status = $response->getStatusCode();

        if ($status === 429) {
            $wait = $this->rateLimit?->retryAfter()
                ?? (float) ($response->getHeaderLine('Retry-After') ?: 1);
            $this->logger->warning("429 on {$request}; holding {$wait}s (" . ($this->rateLimit ?? 'no bucket header') . ')');

            if ($request->getAttempts() >= self::MAX_ATTEMPTS) {
                $request->getDeferred()->reject(new RateLimitException("Rate limited on {$request} after " . self::MAX_ATTEMPTS . ' attempts', 429, null, $response));
                $this->pump();

                return;
            }

            $this->throttled = true;
            $this->loop->addTimer(max(0.1, $wait), function () use ($request): void {
                $this->throttled = false;
                $this->queue->unshift($request);
                $this->pump();
            });

            return;
        }

        if ($status >= 500 || $status === 408) {
            if ($request->getAttempts() < self::MAX_ATTEMPTS) {
                $this->logger->warning("{$status} on {$request} — retrying");
                $this->retry($request, 0.5 * (2 ** ($request->getAttempts() - 1)));

                return;
            }
        }

        if ($status < 200 || $status >= 300) {
            $request->getDeferred()->reject(HttpException::fromResponse($response));
            $this->pump();

            return;
        }

        $body = (string) $response->getBody();
        $decoded = $body === '' ? null : json_decode($body, true);
        $request->getDeferred()->resolve($decoded);
        $this->pump();
    }

    private function retry(Request $request, float $delay): void
    {
        $this->loop->addTimer(max(0.0, $delay), function () use ($request): void {
            $this->queue->unshift($request);
            $this->pump();
        });
    }
}
