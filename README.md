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
```

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
