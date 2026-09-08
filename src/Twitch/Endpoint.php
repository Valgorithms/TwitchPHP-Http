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

/**
 * The set of Twitch Helix endpoints, relative to {@see Http::BASE_URL}.
 *
 * Twitch Helix is almost entirely query-parameter driven — path parameters are
 * rare (only a couple of the extension endpoints use them). Bind those with
 * {@see bind()} / {@see bindAssoc()}; everything else goes through
 * {@see addQuery()} / {@see withQuery()}.
 *
 * @link https://dev.twitch.tv/docs/api/reference/
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 */
class Endpoint
{
    // ── Ads ──────────────────────────────────────────────────────────────
    public const COMMERCIAL              = 'channels/commercial';                 // POST
    public const AD_SCHEDULE             = 'channels/ads';                        // GET
    public const AD_SNOOZE               = 'channels/ads/schedule/snooze';       // POST

    // ── Analytics ────────────────────────────────────────────────────────
    public const EXTENSION_ANALYTICS     = 'analytics/extensions';               // GET
    public const GAME_ANALYTICS          = 'analytics/games';                    // GET

    // ── Bits ─────────────────────────────────────────────────────────────
    public const BITS_LEADERBOARD        = 'bits/leaderboard';                   // GET
    public const CHEERMOTES              = 'bits/cheermotes';                    // GET
    public const EXTENSION_TRANSACTIONS  = 'extensions/transactions';            // GET

    // ── Channels ─────────────────────────────────────────────────────────
    public const CHANNELS                = 'channels';                           // GET, PATCH
    public const CHANNEL_EDITORS         = 'channels/editors';                   // GET
    public const CHANNELS_FOLLOWED       = 'channels/followed';                  // GET
    public const CHANNEL_FOLLOWERS       = 'channels/followers';                 // GET

    // ── Channel Points ───────────────────────────────────────────────────
    public const CUSTOM_REWARDS          = 'channel_points/custom_rewards';                      // GET, POST, PATCH, DELETE
    public const CUSTOM_REWARD_REDEMPTIONS = 'channel_points/custom_rewards/redemptions';        // GET, PATCH

    // ── Charity ──────────────────────────────────────────────────────────
    public const CHARITY_CAMPAIGN        = 'charity/campaigns';                  // GET
    public const CHARITY_DONATIONS       = 'charity/donations';                  // GET

    // ── Chat ─────────────────────────────────────────────────────────────
    public const CHATTERS                = 'chat/chatters';                      // GET
    public const CHANNEL_EMOTES          = 'chat/emotes';                        // GET
    public const GLOBAL_EMOTES           = 'chat/emotes/global';                 // GET
    public const EMOTE_SETS              = 'chat/emotes/set';                    // GET
    public const USER_EMOTES             = 'chat/emotes/user';                   // GET
    public const CHANNEL_CHAT_BADGES     = 'chat/badges';                        // GET
    public const GLOBAL_CHAT_BADGES      = 'chat/badges/global';                 // GET
    public const CHAT_SETTINGS           = 'chat/settings';                      // GET, PATCH
    public const SHARED_CHAT_SESSION     = 'shared_chat/session';                // GET
    public const CHAT_ANNOUNCEMENTS      = 'chat/announcements';                 // POST
    public const SHOUTOUTS               = 'chat/shoutouts';                     // POST
    public const SEND_CHAT_MESSAGE       = 'chat/messages';                      // POST
    public const USER_CHAT_COLOR         = 'chat/color';                         // GET, PUT

    // ── Clips ────────────────────────────────────────────────────────────
    public const CLIPS                   = 'clips';                              // GET, POST

    // ── Content Classification Labels ────────────────────────────────────
    public const CCLS                    = 'content_classification_labels';      // GET

    // ── Conduits (EventSub) ──────────────────────────────────────────────
    public const CONDUITS                = 'eventsub/conduits';                  // GET, POST, PATCH, DELETE
    public const CONDUIT_SHARDS          = 'eventsub/conduits/shards';           // GET, PATCH

    // ── Entitlements ─────────────────────────────────────────────────────
    public const DROPS_ENTITLEMENTS      = 'entitlements/drops';                 // GET, PATCH

    // ── EventSub ─────────────────────────────────────────────────────────
    public const EVENTSUB_SUBSCRIPTIONS  = 'eventsub/subscriptions';             // GET, POST, DELETE

    // ── Extensions ───────────────────────────────────────────────────────
    public const EXTENSION_CONFIGURATION       = 'extensions/configurations';               // GET, PUT
    public const EXTENSION_REQUIRED_CONFIG     = 'extensions/required_configuration';       // PUT
    public const EXTENSION_PUBSUB_MESSAGE      = 'extensions/pubsub';                       // POST
    public const EXTENSION_LIVE_CHANNELS       = 'extensions/live';                         // GET
    public const EXTENSION_SECRETS             = 'extensions/jwt/secrets';                  // GET, POST
    public const EXTENSION_CHAT_MESSAGE        = 'extensions/chat';                         // POST
    public const EXTENSIONS                    = 'extensions';                              // GET
    public const RELEASED_EXTENSIONS          = 'extensions/released';                     // GET
    public const EXTENSION_BITS_PRODUCTS       = 'bits/extensions';                         // GET, PUT

    // ── Games ────────────────────────────────────────────────────────────
    public const TOP_GAMES               = 'games/top';                         // GET
    public const GAMES                   = 'games';                             // GET

    // ── Goals ────────────────────────────────────────────────────────────
    public const GOALS                   = 'goals';                             // GET

    // ── Guest Star ───────────────────────────────────────────────────────
    public const GUEST_STAR_CHANNEL_SETTINGS = 'guest_star/channel_settings';   // GET, PUT
    public const GUEST_STAR_SESSION      = 'guest_star/session';                // GET, POST, DELETE
    public const GUEST_STAR_INVITES      = 'guest_star/invites';                // GET, POST, DELETE
    public const GUEST_STAR_SLOT         = 'guest_star/slot';                   // POST, PATCH, DELETE
    public const GUEST_STAR_SLOT_SETTINGS = 'guest_star/slot_settings';         // PATCH

    // ── Hype Train ───────────────────────────────────────────────────────
    public const HYPE_TRAIN_EVENTS       = 'hypetrain/events';                  // GET

    // ── Moderation ───────────────────────────────────────────────────────
    public const AUTOMOD_STATUS          = 'moderation/enforcements/status';    // POST
    public const AUTOMOD_HELD_MESSAGE    = 'moderation/automod/message';        // POST
    public const AUTOMOD_SETTINGS        = 'moderation/automod/settings';       // GET, PUT
    public const BANNED_USERS            = 'moderation/banned';                 // GET
    public const BANS                    = 'moderation/bans';                   // POST, DELETE
    public const UNBAN_REQUESTS          = 'moderation/unban_requests';         // GET, PATCH
    public const BLOCKED_TERMS           = 'moderation/blocked_terms';          // GET, POST, DELETE
    public const MODERATION_CHAT         = 'moderation/chat';                   // DELETE
    public const MODERATED_CHANNELS      = 'moderation/channels';               // GET
    public const MODERATORS              = 'moderation/moderators';             // GET, POST, DELETE
    public const CHANNEL_VIPS            = 'channels/vips';                     // GET, POST, DELETE
    public const SHIELD_MODE             = 'moderation/shield_mode';            // GET, PUT
    public const WARNINGS                = 'moderation/warnings';               // POST

    // ── Polls ────────────────────────────────────────────────────────────
    public const POLLS                   = 'polls';                             // GET, POST, PATCH

    // ── Predictions ──────────────────────────────────────────────────────
    public const PREDICTIONS             = 'predictions';                       // GET, POST, PATCH

    // ── Raids ────────────────────────────────────────────────────────────
    public const RAIDS                   = 'raids';                             // POST, DELETE

    // ── Schedule ─────────────────────────────────────────────────────────
    public const SCHEDULE                = 'schedule';                          // GET
    public const SCHEDULE_ICALENDAR      = 'schedule/icalendar';               // GET
    public const SCHEDULE_SETTINGS       = 'schedule/settings';                // PATCH
    public const SCHEDULE_SEGMENT        = 'schedule/segment';                 // POST, PATCH, DELETE

    // ── Search ───────────────────────────────────────────────────────────
    public const SEARCH_CATEGORIES       = 'search/categories';                // GET
    public const SEARCH_CHANNELS         = 'search/channels';                  // GET

    // ── Streams ──────────────────────────────────────────────────────────
    public const STREAM_KEY              = 'streams/key';                       // GET
    public const STREAMS                 = 'streams';                           // GET
    public const STREAMS_FOLLOWED        = 'streams/followed';                  // GET
    public const STREAM_MARKERS          = 'streams/markers';                   // GET, POST
    public const STREAM_TAGS             = 'streams/tags';                      // GET (deprecated)

    // ── Subscriptions ────────────────────────────────────────────────────
    public const SUBSCRIPTIONS           = 'subscriptions';                     // GET
    public const SUBSCRIPTION_USER       = 'subscriptions/user';               // GET

    // ── Tags (deprecated) ────────────────────────────────────────────────
    public const ALL_STREAM_TAGS         = 'tags/streams';                      // GET (deprecated)

    // ── Teams ────────────────────────────────────────────────────────────
    public const CHANNEL_TEAMS           = 'teams/channel';                     // GET
    public const TEAMS                   = 'teams';                             // GET

    // ── Users ────────────────────────────────────────────────────────────
    public const USERS                   = 'users';                             // GET, PUT
    public const USER_BLOCKS             = 'users/blocks';                      // GET, PUT, DELETE
    public const USER_EXTENSIONS_LIST    = 'users/extensions/list';             // GET
    public const USER_EXTENSIONS         = 'users/extensions';                  // GET, PUT

    // ── Videos ───────────────────────────────────────────────────────────
    public const VIDEOS                  = 'videos';                            // GET, DELETE

    // ── Whispers ─────────────────────────────────────────────────────────
    public const WHISPERS                = 'whispers';                          // POST

    /** Regex identifying `:param` placeholders in an endpoint. */
    public const REGEX = '/:([^\/]+)/';

    protected string $endpoint;

    /** @var list<string> */
    protected array $vars = [];

    /** @var array<string, string> */
    protected array $args = [];

    /** @var array<string, string|int|bool|array<int|string, string|int>> */
    protected array $query = [];

    public function __construct(string $endpoint)
    {
        $this->endpoint = ltrim($endpoint, '/');

        if (preg_match_all(self::REGEX, $this->endpoint, $matches)) {
            $this->vars = $matches[1];
        }
    }

    /**
     * Creates an endpoint and binds positional path arguments to it.
     */
    public static function bind(string $endpoint, string|int ...$args): self
    {
        return (new self($endpoint))->bindArgs(...$args);
    }

    /** Binds positional path arguments, in the order the `:params` appear. */
    public function bindArgs(string|int ...$args): self
    {
        foreach ($this->vars as $i => $var) {
            if (array_key_exists($i, $args)) {
                $this->args[$var] = (string) $args[$i];
            }
        }

        return $this;
    }

    /**
     * Binds path arguments by name.
     *
     * @param array<string, string|int> $args
     */
    public function bindAssoc(array $args): self
    {
        foreach ($args as $k => $v) {
            $this->args[$k] = (string) $v;
        }

        return $this;
    }

    /** Adds one query-string pair. Arrays are repeated (`?id=1&id=2`). */
    public function addQuery(string $key, string|int|bool|array $value): self
    {
        $this->query[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;

        return $this;
    }

    /**
     * Merges an associative array of query pairs, skipping null values.
     *
     * @param array<string, string|int|bool|array<int, string|int>|null> $query
     */
    public function withQuery(array $query): self
    {
        foreach ($query as $k => $v) {
            if ($v !== null) {
                $this->addQuery($k, $v);
            }
        }

        return $this;
    }

    /** @return array<string, string|int|bool|array<int|string, string|int>> */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Renders the endpoint with path params substituted and the query string
     * appended. Twitch repeats array params rather than using `key[]`.
     */
    public function toAbsoluteEndpoint(bool $onlyMajorParameters = false): string
    {
        $endpoint = $this->endpoint;

        foreach ($this->vars as $var) {
            if (isset($this->args[$var])) {
                $endpoint = str_replace(":{$var}", rawurlencode($this->args[$var]), $endpoint);
            }
        }

        if (! $onlyMajorParameters && $this->query !== []) {
            $endpoint .= '?' . $this->buildQuery();
        }

        return $endpoint;
    }

    private function buildQuery(): string
    {
        $parts = [];
        foreach ($this->query as $key => $value) {
            foreach ((array) $value as $item) {
                $parts[] = rawurlencode($key) . '=' . rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }

    public function __toString(): string
    {
        return $this->toAbsoluteEndpoint();
    }
}
