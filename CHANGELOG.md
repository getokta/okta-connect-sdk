# Changelog

All notable changes to `getokta/okta-connect-sdk` are documented in this file.

The format is loosely based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
