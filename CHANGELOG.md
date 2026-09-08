# Changelog

All notable changes to `twitchphp/http` are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses SemVer.

## [1.0.0] - 2026-09-08

Initial usable release. The package previously contained an unfinished copy of
`discord-php/http` (Discord's endpoint map, Discord's rate-limit model, no
`Client-Id` header); this is a ground-up rewrite for Twitch.

### Added
- `Twitch\Http\Http` — async Helix client (`get`/`post`/`put`/`patch`/`delete`),
  bounded concurrency queue, `429` handling via `Ratelimit-Reset`, transient
  `5xx` retry with backoff, `Client-Id` + `Bearer` headers, `setToken()` for
  refresh, `Http::create()` convenience constructor.
- `Twitch\Http\Endpoint` — the Twitch Helix endpoint map (~90 constants across
  ads, analytics, bits, channels, channel points, charity, chat, clips, CCLs,
  conduits, entitlements, EventSub, extensions, games, goals, guest star, hype
  train, moderation, polls, predictions, raids, schedule, search, streams,
  subscriptions, teams, users, videos, whispers) plus path/query binding.
- `Twitch\Http\OAuth` — client-credentials, authorization-code (`authorizeUrl`
  + `exchangeCode`), `refreshToken`, device-code (`deviceCode` +
  `pollDeviceToken`), `validate`, `revoke`.
- `Twitch\Http\RateLimit` — a snapshot of the `Ratelimit-*` bucket headers.
- `Twitch\Http\DriverInterface` + `Drivers\React` — the pluggable transport.
- Typed exception hierarchy under `Twitch\Http\Exceptions` with a
  `HttpException::fromResponse()` factory.
