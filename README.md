# Okta Connect PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-alpha-orange)](#)

A framework-agnostic PHP client for the **Okta Connect** omnichannel messaging platform.
WhatsApp ships first; SMS and email channels follow under the same client surface
(`Okta\Connect\<Channel>\*`). No Laravel dependency in the SDK code itself — a separate
`getokta/okta-connect-sdk-laravel` bridge package will be published later.

## Installation

```bash
composer require getokta/okta-connect-sdk
```

## Quickstart

### Tenant scope (read / write / send)

```php
use Okta\Connect\WhatsApp\Client;

$client = new Client(
    baseUrl: 'https://connect.example.com',
    token: 'sanctum_token_here',
    options: ['timeout' => 30, 'retries' => 2],
);

$client->messages()->send([
    'channel_id' => '01H...',
    'to'         => '+9665...',
    'type'       => 'text',
    'text'       => ['body' => 'Hello'],
]);

$messages      = $client->messages()->list(['conversation_id' => '01H...', 'per_page' => 50]);
$conversations = $client->conversations()->list();
$conversation  = $client->conversations()->get($id);
$contacts      = $client->contacts()->list(['search' => '+966']);
$client->contacts()->upsert(['phone' => '+9665...', 'name' => 'Ali']);
$channels      = $client->channels()->list();
$client->webhooks()->register(['url' => 'https://...', 'events' => ['message.received']]);

// Meta message templates
$templates = $client->templates()->list(['status' => 'APPROVED', 'language' => 'ar']);
$client->templates()->send([
    'channel_id'    => '01H...',
    'wa_id'         => '966500000000',
    'template_name' => 'order_ready',
    'language'      => 'ar',
    'variables'     => ['12345', '120 SAR'],
]);

// WhatsApp groups (Baileys-only)
$group = $client->groups()->create('Sales pod', ['966500000000', '966500000001']);
$client->groups()->addParticipants($group->id, ['966500000002']);
```

### Platform-admin scope (`platform.admin` ability)

```php
$client->admin()->workspaces()->create(['name' => 'Acme', 'slug' => 'acme']);
$client->admin()->workspaces()->list(['per_page' => 20]);
$client->admin()->workspaces()->get($ulid);
$client->admin()->workspaces()->update($ulid, ['display_name' => 'Acme Inc']);

$client->admin()->workspaceUsers()->create($workspaceId, [
    'name' => 'Ali', 'email' => 'ali@acme.com', 'password_auto' => true,
]);
$client->admin()->workspaceUsers()->list($workspaceId);

$client->admin()->workspaceTokens()->issue($workspaceId, [
    'name' => 'partner-app',
    'abilities' => ['read', 'send'],
    'user_id' => $userId,
]);

$client->admin()->workspaceChannels()->create($workspaceId, [
    'display_name' => 'Acme Main',
    'type'         => 'whatsapp_cloud',
]);
$client->admin()->workspaceChannels()->list($workspaceId);

// Platform transactional messaging (outbound-only; needs platform.admin or platform.inbox)
$client->admin()->messages()->transactional([
    'to'   => '+966500000000',
    'type' => 'text',
    'text' => 'Your order #1234 has shipped.',
]);
$client->admin()->messages()->otp([
    'to'          => '+966500000000',
    'code'        => '482915',
    'ttl_seconds' => 300,
    'purpose'     => 'two_factor',
    'locale'      => 'ar',
]);

// Embed-SSO secrets
$legacySecret = $client->admin()->embedSecret()->sync();              // iss=okta-web
$partner      = $client->admin()->embedSecret()->provision('salla', 'salla-app');
// $partner = ['label' => 'salla', 'issuer' => 'salla-app', 'secret' => '…', 'created' => true]
```

### Embedding the inbox (iframe)

Mint embed tokens and build iframe URLs natively — no hand-rolled JWTs. Fetch the
shared secret once (`admin()->embedSecret()->sync()` or `provision()`), then:

```php
use Okta\Connect\WhatsApp\Embed\EmbedUser;
use Okta\Connect\WhatsApp\Embed\UiHide;

$embed = $client->embed($sharedSecret);              // base URL reused from the client
$operator = new EmbedUser(sub: 'partner-user-7', email: 'op@acme.com', name: 'Op');

// Cookieless per-request flow (survives third-party-cookie blocking). The token
// rides every request inside the iframe — recommended for white-label embeds.
$src = $embed->inboxUrl($operator, uiHide: [UiHide::AI, UiHide::ASSIGN_AGENT]);
// <iframe src="<?= $src ?>"></iframe>

// …or attach the header to your own XHR/fetch calls instead of the query string:
$headers = $embed->tokenHeader($embed->sessionToken($operator));

// One-shot SSO landing handshake (same-site cookies):
$ssoUrl = $embed->ssoUrl($operator, redirectPath: '/app/inbox?embedded=1');
```

Unknown `ui_hide` keys and out-of-range TTLs throw at mint time, so misconfigured
embeds fail loudly here instead of silently in the browser.

### Idempotency

Mutating calls accept an optional `Idempotency-Key` header so safe retries are server-deduped:

```php
$client->messages()->send($payload, idempotencyKey: 'order-1234-confirmation');
```

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `baseUrl` | `string` | _required_ | Root URL of the Okta Connect API, e.g. `https://connect.example.com`. |
| `token` | `string` | _required_ | Sanctum personal-access token. Abilities determine which endpoints succeed. |
| `timeout` | `int` | `30` | Request timeout in seconds. |
| `retries` | `int` | `2` | Retry budget for 429 + 5xx responses (exponential backoff, base 250 ms). |
| `httpClient` | `Psr\Http\Client\ClientInterface` | Guzzle | Inject a custom PSR-18 client (testing, alt transports). |

## Error handling

All non-2xx responses raise typed exceptions extending `Okta\Connect\WhatsApp\Exceptions\WhatsAppException`:

| Exception | HTTP | Meaning |
|---|---|---|
| `AuthenticationException` | 401 | Token missing / invalid / expired. |
| `AuthorizationException`  | 403 | Token lacks the required ability for this endpoint. |
| `NotFoundException`       | 404 | Resource doesn't exist or is not visible to the caller. |
| `ValidationException`     | 422 | Request body failed validation. Use `->errors()` to read the field map. |
| `RateLimitException`      | 429 | Exceeded the per-token rate limit. Use `->retryAfter()` to back off. |
| `ServerException`         | 5xx | Server error. SDK will retry per `retries` config before raising. |
| `WhatsAppException`       | other | Base class — catch-all for unexpected status codes. |

Each exception exposes `->statusCode()`, `->responseBody()`, and the original PSR-7 response.

## Running the tests

```bash
composer install
vendor/bin/phpunit
```

## License

MIT — see [LICENSE](LICENSE).
