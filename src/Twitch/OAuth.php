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
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

use React\Socket\Connector;
use Twitch\Http\Exceptions\HttpException;
use Twitch\Http\Exceptions\InvalidTokenException;

/**
 * Thin async wrapper around the Twitch OAuth2 endpoints on `id.twitch.tv`.
 *
 * Supports the flows a bot or service needs:
 *  - {@see clientCredentials()} — an app access token (no user context).
 *  - {@see authorizeUrl()} + {@see exchangeCode()} — the authorization-code
 *    flow for a user access token.
 *  - {@see refreshToken()} — trade a refresh token for a fresh access token.
 *  - {@see deviceCode()} + {@see pollDeviceToken()} — the device-code flow for
 *    headless clients.
 *  - {@see validate()} / {@see revoke()}.
 *
 * @link https://dev.twitch.tv/docs/authentication/
 */
final class OAuth
{
    public const BASE = 'https://id.twitch.tv/oauth2';

    private Browser $browser;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret = '',
        ?LoopInterface $loop = null,
        array $socketOptions = [],
    ) {
        $loop ??= Loop::get();
        $this->browser = (new Browser($socketOptions === [] ? null : new Connector($socketOptions, $loop), $loop))
            ->withRejectErrorResponse(false)
            ->withTimeout(20.0);
    }

    /**
     * App access token via the client-credentials grant.
     *
     * @return PromiseInterface<array{access_token: string, expires_in: int, token_type: string}>
     */
    public function clientCredentials(): PromiseInterface
    {
        return $this->token([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);
    }

    /**
     * The URL to send a user to so they can authorize the application.
     *
     * @param list<string> $scopes
     */
    public function authorizeUrl(string $redirectUri, array $scopes = [], ?string $state = null, bool $forceVerify = false): string
    {
        $query = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
        ];
        if ($state !== null) {
            $query['state'] = $state;
        }
        if ($forceVerify) {
            $query['force_verify'] = 'true';
        }

        return self::BASE . '/authorize?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Trade an authorization code for a user access + refresh token.
     *
     * @return PromiseInterface<array{access_token: string, refresh_token: string, expires_in: int, scope: list<string>, token_type: string}>
     */
    public function exchangeCode(string $code, string $redirectUri): PromiseInterface
    {
        return $this->token([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * @return PromiseInterface<array{access_token: string, refresh_token: string, expires_in: int, scope: list<string>, token_type: string}>
     */
    public function refreshToken(string $refreshToken): PromiseInterface
    {
        return $this->token([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Start the device-code flow.
     *
     * @param list<string> $scopes
     *
     * @return PromiseInterface<array{device_code: string, user_code: string, verification_uri: string, expires_in: int, interval: int}>
     */
    public function deviceCode(array $scopes = []): PromiseInterface
    {
        return $this->form(self::BASE . '/device', [
            'client_id' => $this->clientId,
            'scopes' => implode(' ', $scopes),
        ]);
    }

    /**
     * Exchange a device code for a token. Rejects with a {@see HttpException}
     * carrying `authorization_pending` until the user completes the flow.
     *
     * @return PromiseInterface<array{access_token: string, refresh_token: string, expires_in: int, scope: list<string>, token_type: string}>
     */
    public function pollDeviceToken(string $deviceCode): PromiseInterface
    {
        return $this->token([
            'client_id' => $this->clientId,
            'scopes' => '',
            'device_code' => $deviceCode,
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
        ]);
    }

    /**
     * Validate a token. Rejects with {@see InvalidTokenException} when expired.
     *
     * @return PromiseInterface<array{client_id: string, login?: string, scopes?: list<string>, user_id?: string, expires_in: int}>
     */
    public function validate(string $token): PromiseInterface
    {
        return $this->browser->get(self::BASE . '/validate', ['Authorization' => 'OAuth ' . $token])
            ->then(function (ResponseInterface $response) {
                if ($response->getStatusCode() === 401) {
                    throw new InvalidTokenException('The token is invalid or expired', 401, 'Unauthorized', $response);
                }

                return $this->decode($response);
            });
    }

    /** @return PromiseInterface<null> */
    public function revoke(string $token): PromiseInterface
    {
        return $this->form(self::BASE . '/revoke', ['client_id' => $this->clientId, 'token' => $token])
            ->then(static fn () => null);
    }

    /** @param array<string, string> $params */
    private function token(array $params): PromiseInterface
    {
        return $this->form(self::BASE . '/token', $params);
    }

    /**
     * @param array<string, string> $params
     *
     * @return PromiseInterface<array<string, mixed>>
     */
    private function form(string $url, array $params): PromiseInterface
    {
        if ($this->clientSecret === '' && ! isset($params['device_code'])) {
            return reject(new HttpException('This OAuth flow needs a client secret'));
        }

        return $this->browser->post(
            $url,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query($params, '', '&', PHP_QUERY_RFC3986),
        )->then(fn (ResponseInterface $response) => $this->decode($response));
    }

    /** @return array<string, mixed> */
    private function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if ($response->getStatusCode() >= 300 || ! is_array($data)) {
            throw HttpException::fromResponse($response);
        }

        return $data;
    }
}
