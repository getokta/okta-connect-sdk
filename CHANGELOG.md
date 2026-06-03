# Changelog

All notable changes to `getokta/okta-connect-sdk` are documented in this file.

The format is loosely based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
