# Changelog

All notable changes to `twitchphp/http` are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses SemVer.

## [Unreleased]

## [1.1.0] - 2026-09-23

### Added

- `MissingScopeException` — raised when the token is valid but was not granted
  a scope the endpoint requires. Deliberately *not* an `InvalidTokenException`,
  because re-issuing the token cannot widen a grant. Its `$scopes` property
  holds the scopes Twitch named, any one of which would have satisfied the call.
- `Endpoint` constants for the Helix operations the Twitch OpenAPI spec
  documents and the map lacked: `HYPE_TRAIN_STATUS`, `CUSTOM_POWER_UPS`,
  `CHAT_PINS`, `CLIP_FROM_VOD`, `CLIP_DOWNLOADS` and `SUSPICIOUS_USERS`.

### Deprecated

- `Endpoint::HYPE_TRAIN_EVENTS` — Twitch withdrew the endpoint and it answers
  410. Use `HYPE_TRAIN_STATUS`. Kept so existing callers still compile.

### Fixed

- 401 responses are no longer all mapped to `InvalidTokenException`. Twitch
  overloads the status for three unrelated conditions, and only one is worth
  re-issuing a token over: a narrow grant now raises `MissingScopeException`,
  an endpoint demanding a different kind of token (e.g. conduits, which accept
  app access tokens only) raises a plain `HttpException`, and everything else —
  including wording we do not recognise — still raises `InvalidTokenException`
  so a genuine expiry keeps triggering a refresh.

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
