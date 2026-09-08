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
use React\Promise\PromiseInterface;

/**
 * Executes a single prepared {@see Request} and resolves with the raw
 * {@see ResponseInterface} — success or error status alike. Retry, rate-limit,
 * and exception mapping are {@see Http}'s job, not the driver's.
 */
interface DriverInterface
{
    /**
     * @return PromiseInterface<ResponseInterface>
     */
    public function runRequest(Request $request): PromiseInterface;
}
