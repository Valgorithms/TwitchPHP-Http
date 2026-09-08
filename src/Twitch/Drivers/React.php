<?php

/*
 * This file is a part of the TwitchPHP-Http project.
 *
 * Copyright (c) 2025-present Valithor Obsidion <valithor@valgorithms.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Twitch\Http\Drivers;

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use Twitch\Http\DriverInterface;
use Twitch\Http\Request;

/**
 * The default {@see DriverInterface}: a non-blocking {@see Browser} from
 * `react/http`. Configured to surface 4xx/5xx as resolved responses (not
 * rejections) so {@see \Twitch\Http\Http} can map them to typed exceptions and
 * retry where appropriate.
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 */
final class React implements DriverInterface
{
    private Browser $browser;

    /**
     * @param array<string, mixed> $socketOptions Passed to `React\Socket\Connector`
     *                                            (e.g. a `tls.cafile` for Windows).
     */
    public function __construct(LoopInterface $loop, array $socketOptions = [])
    {
        $this->browser = (new Browser($socketOptions === [] ? null : new Connector($socketOptions, $loop), $loop))
            ->withRejectErrorResponse(false)
            ->withTimeout(30.0)
            ->withFollowRedirects(false);
    }

    /**
     * @return PromiseInterface<ResponseInterface>
     */
    public function runRequest(Request $request): PromiseInterface
    {
        return $this->browser->request(
            $request->getMethod(),
            $request->getUrl(),
            $request->getHeaders(),
            $request->getContent(),
        );
    }
}
