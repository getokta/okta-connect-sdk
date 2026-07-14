# Changelog

All notable changes to `getokta/okta-connect-sdk` are documented in this file.

The format is loosely based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] — 2026-07-14

### Added
- **`Resources\Webhooks` — full lifecycle management.** In addition to the
  existing `register()`, the resource now offers:
  - `create(array $payload, ?string $idempotencyKey = null): DTO\Webhook` —
    register a subscription and receive a typed `Webhook`. The signing
    `secret` is present on `$webhook->secret` **exactly once** (on create) and
    is never returned again.
  - `list(array $filters = []): PaginatedResult<Webhook>` — the org's
    subscriptions (no secret).
  - `delete(string $id): bool` — remove a subscription by ULID.
  - `Webhooks::verifySignature(string $rawBody, string $signatureHeader, string $secret): bool`
    — constant-time (`hash_equals`) check of the `X-Okta-Signature` header.
- **`Enums\WebhookEvent`** — a backed string enum of every outbound event name,
  value-identical to the platform's dotted strings, including the new lifecycle
  events `subscription.activated|cancelled|expired|past_due` and
  `channel.connected|disconnected|deleted`. Helpers `isChannelLifecycle()` and
  `isSubscription()`.
- **`DTO\Webhook`** — typed subscription record (`id`, `name`, `url`, `events`,
  `isActive`, `maxAttempts`, `secret`, `createdAt`).

## [1.0.0] — 2026-07-13

### Changed — BREAKING
- **`Enums\ChannelType` is now value-identical to the platform API.** The
  WhatsApp cases were renamed:
  - `ChannelType::WhatsAppCloud` (`'whatsapp_cloud'`) → `ChannelType::CloudApi` (`'cloud_api'`)
  - `ChannelType::WhatsAppBaileys` (`'whatsapp_baileys'`) → `ChannelType::Baileys` (`'baileys'`)

  The old values never matched real API responses (the API serialises the
  platform enum's raw value), so `Channel->type` silently hydrated to `null`
  for every WhatsApp channel. Upgrade by replacing the two case references;
  string comparisons against `'whatsapp_cloud'` / `'whatsapp_baileys'` must
  switch to `'cloud_api'` / `'baileys'`.

### Added
- `ChannelType::Embed` (`'embed'`) and `ChannelType::Messenger`
  (`'messenger'`) — the two platform types the enum was missing, so
  `tryFrom()` now resolves every value the API can return.
- `ChannelType::isWhatsApp()` — true for the family the API's
  `type=whatsapp` filter alias expands to (`cloud_api` + `baileys`).
- `ChannelType::isSocial()` — true for the non-WhatsApp social platforms.

## [0.9.0] — 2026-07-13

### Added
- **HTML email designer — `Email\HtmlMessageBuilder`.** Fluent builder that
  produces email-client-safe HTML (table-based layout, all CSS inlined,
  600px centered card — renders in Gmail/Outlook) so callers can design
  branded messages without writing raw markup.
  - `make(bool $rtl = true)` — RTL-first (dir/lang + text alignment flip
    together); pass `false` for LTR.
  - Styling: `brandColor($hex)` (default `#10b981`), `backgroundColor($hex)`
    (default `#f8fafc`), `logo($url, $width)`, `preheader($text)` (hidden
    inbox preview), `footer($text)` (muted small print).
  - Content blocks, rendered in insertion order: `heading`, `paragraph`,
    `button($label, $url)` (bulletproof brand-colour pill), `divider`,
    `spacer($px)`, `image($url, $alt)`, and a raw `html($block)` escape hatch.
  - Every user-supplied string is HTML-escaped except `html()`.
  - `toHtml()` returns the complete `<!DOCTYPE html>` document;
    `__toString()` aliases it, so a builder drops straight into `send()`
    payloads or `sendHtml()`.
- **`emails()->sendHtml($from, $to, $subject, $html, $overrides, ?idempotencyKey)`**
  — convenience for sending a pre-rendered HTML body: a bare string `$to` is
  normalised to a list and any `Stringable` (e.g. an `HtmlMessageBuilder`)
  is cast to its markup before delegating to `send()`.
- **Channel type/status filtering — `channels()`.** `GET /api/v1/channels`
  now accepts `type` (a channel type value — `cloud_api` / `baileys` /
  `telegram` / `instagram_dm` / `twitter` / `linkedin` / `tiktok` / `email` —
  or the family alias `whatsapp`, covering cloud_api + baileys) and `status`
  (`connected` / `disconnected` / `pending` / `failed`); `list($filters)`
  passes both through. Typed helpers:
  - `listByType($type, ?$status, $extra)` — merges type/status into the
    filters (status only when non-null).
  - `whatsapp(?$status)` — the `whatsapp` family alias.
  - `connected(?$type)` / `disconnected(?$type)` — status presets,
    optionally narrowed to one type.
- `Enums\ChannelType` gained the omnichannel cases: `Telegram`,
  `InstagramDm`, `Twitter`, `LinkedIn`, `TikTok`, `Email`.

### Changed
- User-Agent bumped to `okta-connect-sdk-php/0.9`.

## [0.8.0] — 2026-07-09

### Added
- **Email — `Client::emails()`.** Full transactional + bulk email surface, the
  new headline of this release.
  - `send($payload, ?idempotencyKey)` → `EmailMessage` (`POST /api/v1/emails`):
    send as one of your verified sending domains (`from`, `to[]`, `subject`,
    `html`/`text`, `cc`/`bcc`, `reply_to`, `headers`). DKIM-signed server-side.
  - `sendTemplate($from, $to, $template, $variables, $overrides, ?idempotencyKey)`
    — render a stored template by slug/ULID with a `variables` map.
  - `list($filters)` → `PaginatedResult<EmailMessage>` (send log, `status` filter);
    `get($ulid)` → `EmailMessage`.
  - `analytics($from, $to)` → `EmailAnalytics` — status-count summary
    (delivery/bounce rates) + a per-day series (`GET /api/v1/emails/analytics`).
  - Nested managers:
    - `emails()->templates()` — `list`/`get`/`create`/`update`/`delete`
      (`/api/v1/email-templates`).
    - `emails()->broadcasts()` — `list`/`get`/`create` a draft + `queue($ulid)`
      to fan out to a CRM-tag audience (`/api/v1/emails/broadcasts`).
    - `emails()->suppressions()` — `list`/`add($address)`/`remove($idOrAddress)`
      for the org suppression list (`/api/v1/emails/suppressions`).
- **Social publishing — `Client::socialPosts()`.** Compose a post and fan it out
  to one or more social channels (Telegram, X, Instagram, …).
  - `schedule($text, $channelIds, $scheduledAt, $media)` / `draft($text, $channelIds, $media)`
    typed helpers, plus raw `create($payload)`, `list($filters)`, `get($ulid)`
    (`/api/v1/social-posts`).
  - `SocialPost` carries per-channel `targets` (`SocialPostTarget`: status,
    permalink, provider_post_id) so you can read each platform's outcome —
    including the upstream failure reason on a partially-failed fan-out.
- **Campaigns — `Client::campaigns()`.** Broadcast (bulk/drip) message campaigns:
  `list`/`get`/`create` a draft + `queue($ulid)` to materialise the audience and
  start sending (`/api/v1/campaigns`).

### New DTOs
- `EmailMessage`, `EmailTemplate`, `EmailBroadcast`, `EmailSuppression`,
  `EmailAnalytics`, `SocialPost`, `SocialPostTarget`, `Campaign`.

### Changed
- User-Agent bumped to `okta-connect-sdk-php/0.8`.

## [0.7.0] — 2026-07-08

### Fixed
- **`messages()` send payload shape.** The documented `Messages::send()` example
  posted a WhatsApp-Cloud-style body (`to` + `text.body`), but the platform's
  `POST /api/v1/messages` validates a FLAT shape (`channel_id` + `wa_id`, or
  `conversation_id`, plus a flat `body`) — so following the README produced a
  422 and no message was sent. The raw `send()` and the README now use the
  correct flat shape.

### Added
- **Typed send helpers** on `messages()` that always build the correct request
  shape, so callers can't get it wrong:
  - `sendText(string $channelId, string $waId, string $body, ?string $idempotencyKey = null)`
  - `sendMedia(string $channelId, string $waId, string $type, string $mediaUrl, string $caption = '', ?string $idempotencyKey = null)`
  - `reply(string $conversationId, string $body, ?string $idempotencyKey = null)`

### Removed (security hardening) — BREAKING
- **Dropped the entire platform-admin surface from the public SDK.** Removed
  `AdminClient` (`Client::admin()`), all `Resources\Admin\*` (Workspaces,
  Organizations, WorkspaceUsers, WorkspaceTokens, WorkspaceChannels,
  EmbedSecret, admin Messages) and the admin-only `ProvisionedOrganization`
  DTO. These wrapped privileged `platform.admin` endpoints (org/workspace
  provisioning, API-token minting, embed-secret provisioning) and should not
  ship in a public developer package — publishing them needlessly documented
  the privileged attack surface. The server-side endpoints are unchanged and
  remain callable directly from a trusted backend by the platform operator.
- The developer-facing surface (messages, conversations, contacts, channels,
  templates, groups, webhooks, Meta/QR integrations, embed token minting) is
  unchanged.

## [0.6.0] — 2026-06-03

### Added
- **Native embed integration** — `Okta\Connect\WhatsApp\Embed\Embed`, reachable
  via `Client::embed($sharedSecret)` (the configured base URL is reused). This
  brings the full iframe-embed handshake into the SDK so partner platforms stop
  hand-rolling it (the recurring source of embed breakage).
  - One-shot SSO landing: `ssoToken()` / `ssoUrl()` (≤5 min, replay-checked
    server-side) for the `/embed/sso` redirect handshake.
  - **Cookieless per-request flow**: `sessionToken()` (≤4 h, no replay),
    `embedUrl($path, …)` (appends `?embed_token=`), `inboxUrl()` preset, and
    `tokenHeader()` for the `X-Embed-Token` header. The previous `TokenMinter`
    capped TTL at 300 s and **could not mint this token at all** — the reason
    every platform forked its own JWT code.
  - `Embed\EmbedUser` value object (sub/email/name) so positional args can't be
    transposed into "logged in as the wrong account" bugs.
  - `Embed\UiHide` canonical feature-key constants + `validate()`; unknown
    `ui_hide` keys now throw at mint time instead of failing open (a control
    silently staying visible).
  - Scope, TTL ceilings, `iss`/`aud`, and the `ui_hide` shape mirror the
    platform's `EmbedSsoVerifier` / `EmbedStatelessVerifier` exactly.

### Deprecated
- `Sso\TokenMinter` — superseded by `Embed\Embed`. Still works (one-shot SSO
  only); migrate to `Client::embed()`.

### Changed
- User-Agent bumped to `okta-connect-sdk-php/0.6`.

## [0.5.0] — 2026-06-03

### Added
- `Client::templates()` — Meta message templates.
  - `list($filters)` → `list<Template>`; optional `status` / `language` filters
    (`GET /api/v1/templates`).
  - `send($payload)` queues an approved template to a `wa_id` with positional
    `variables` and returns the resulting `Message` (`POST /api/v1/templates/send`).
- `Client::groups()` — WhatsApp groups (Baileys-only): list/get + create/rename
  + add/remove participants + set picture + force resync. *(Shipped in code in
  0.4.x; first formally released and tagged here.)*
- `AdminClient::messages()` — platform-workspace transactional messaging
  (outbound-only, never fans out to the agent inbox; needs `platform.admin`
  or `platform.inbox`).
  - `transactional($payload)` — one-shot `text` or Cloud API `template`
    (`POST /api/v1/admin/messages/transactional`).
  - `otp($payload)` — one-time password over WhatsApp, server-throttled per
    destination phone (`POST /api/v1/admin/messages/otp`).
- `AdminClient::embedSecret()->provision($label, $issuer)` — provision a
  labelled per-partner embed-SSO secret bound to a specific JWT issuer in one
  round-trip (`POST /api/v1/admin/embed-secret/provision`). Complements the
  legacy `sync()` (which always returns the `iss=okta-web` secret).

### New DTOs
- `Template`, `TransactionalMessage`.

### Changed
- User-Agent bumped to `okta-connect-sdk-php/0.5`.

## [0.4.0] — 2026-05-11

### Added
- `Client::meta()` — native WhatsApp Embedded Signup (Cloud API) for partner UIs.
  - `config()` → `MetaConfig` DTO carrying the Meta JS SDK parameters (`app_id`,
    `config_id`, `graph_version`, `signup_mode`, `available`) the partner needs
    to boot `connect.facebook.net/en_US/sdk.js` and invoke `FB.login`.
  - `completeEmbeddedSignup($code, $wabaId)` finalises the signup; returns
    `list<EmbeddedSignupChannel>`.
- `Client::qr()` — QR pairing companion for non-Cloud-API numbers.
  - `start($displayName)` creates a workspace-scoped channel and boots the
    pairing session.
  - `status($ulid)` polls for the current QR string + TTL + channel status.
  - `QrSession::isConnected()` / `isTerminal()` convenience predicates.
- `AdminClient::organizations()->create()` — backend-to-backend workspace
  provisioning (`POST /api/v1/admin/organizations`). Returns a
  `ProvisionedOrganization` DTO carrying the created Organization + owner User
  + a usable Sanctum access token.
- `AdminClient::embedSecret()->sync()` — fetch (and lazily provision) the
  platform's HS256 secret used to verify `/embed/sso` JWTs.
- `Okta\Connect\WhatsApp\Sso\TokenMinter` — pure-crypto HS256 JWT minter for
  the embed-SSO handshake (`mint()` + `ssoUrl()`).

### New DTOs
- `MetaConfig`, `EmbeddedSignupChannel`, `QrSession`, `ProvisionedOrganization`.

### Changed
- User-Agent bumped to `okta-connect-sdk-php/0.4`.

## [0.2.0] — 2026-05-10

Initial public scaffolding: tenant resources (`messages`, `conversations`,
`contacts`, `channels`, `webhooks`) + admin namespace (`workspaces`,
`workspaceUsers`, `workspaceTokens`, `workspaceChannels`).

## [0.1.0] — 2026-05-09

Project bootstrap.
