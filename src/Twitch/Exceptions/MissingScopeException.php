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
 * The token is valid, but was not granted a scope the endpoint requires.
 *
 * Deliberately *not* an {@see InvalidTokenException}: refreshing or
 * re-issuing the token changes nothing, because the grant itself is too
 * narrow. Recovering means sending the user back through authorization
 * with the missing scopes requested.
 *
 * {@see $scopes} holds the scopes Twitch named, any *one* of which would
 * have satisfied the endpoint.
 */
class MissingScopeException extends HttpException
{
    /** @var list<string> */
    public readonly array $scopes;

    public function __construct(
        string $message,
        int $status = 401,
        ?string $error = null,
        ?ResponseInterface $response = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $error, $response, $previous);

        $this->scopes = self::parseScopes($message);
    }

    /**
     * Pulls the scope names out of `Missing scope: a:b or c:d`.
     *
     * @return list<string>
     */
    private static function parseScopes(string $message): array
    {
        if (! preg_match('/missing scope:\s*(.+)$/i', $message, $matches)) {
            return [];
        }

        $scopes = preg_split('/\s+or\s+|\s*,\s*/i', trim($matches[1])) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $scope): string => trim($scope, " \t.\r\n"),
            $scopes,
        ), static fn (string $scope): bool => $scope !== ''));
    }
}
