# TwitchPHP-Http

Non-blocking HTTP client and OAuth2 helper for the [Twitch Helix API](https://dev.twitch.tv/docs/api/),
built on [ReactPHP](https://reactphp.org). It is the transport layer used by
[TwitchPHP](https://github.com/valzargaming/TwitchPHP) — the same split as
`discord-php/http` under DiscordPHP — and is usable on its own.

- Every call gets the `Authorization: Bearer` and `Client-Id` headers Twitch requires.
- A bounded concurrency queue, automatic `429` handling (honouring `Ratelimit-Reset`),
  and transient `5xx` retry with backoff.
- Typed exceptions (`InvalidTokenException`, `NoPermissionsException`, `RateLimitException`, …).
- `Twitch\Http\OAuth` covers the client-credentials, authorization-code, refresh,
  and device-code flows, plus `validate` / `revoke`.

## Install

```bash
composer require twitchphp/http
```

Requires PHP 8.1+.

## Usage

```php
use React\EventLoop\Loop;
use Twitch\Http\Endpoint;
use Twitch\Http\Http;
use Twitch\Http\OAuth;

$loop = Loop::get();
$oauth = new OAuth('client-id', 'client-secret', $loop);

$oauth->clientCredentials()->then(function (array $token) use ($loop) {
    $http = Http::create($token['access_token'], 'client-id', null, $loop);

    // GET https://api.twitch.tv/helix/users?login=twitchdev
    $http->get(
        (new Endpoint(Endpoint::USERS))->addQuery('login', 'twitchdev')
    )->then(function (?array $body) {
        // $body === ['data' => [[...user...]], 'pagination' => []]
        print_r($body['data'][0]);
    });
});

$loop->run();
```

`Http` resolves with the decoded JSON body untouched — Twitch's
`{ "data": [...], "pagination": { "cursor": "..." } }` envelope is passed
straight through, and a `204` resolves with `null`. Higher-level unwrapping,
Parts, repositories and events live in [TwitchPHP](https://github.com/valzargaming/TwitchPHP).

## License

MIT — see [LICENSE](LICENSE).
