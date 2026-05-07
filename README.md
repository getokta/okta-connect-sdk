# Okta WhatsApp PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-alpha-orange)](#)

A framework-agnostic PHP client for the Okta WhatsApp platform's HTTP API. Wraps the tenant
messaging endpoints and the platform-admin workspace-management endpoints into a typed,
testable client. No Laravel dependency in the SDK code itself — a separate
`getokta/whatsapp-sdk-laravel` bridge package will be published later.

> **Status:** Phase 1A monorepo package. Lives at `packages/whatsapp-sdk/` inside
> `getokta/okta-whatsapp` until it is split out via `git subtree split` into its own
> repository at `getokta/okta-whatsapp-sdk` and published to Packagist.

## Installation

### From this monorepo (current state)

While the package lives inside `getokta/okta-whatsapp`, consumer apps install it via a Composer
path repository pointing at their local checkout:

```jsonc
// composer.json (consumer app, e.g. tahdirit/okta-web)
{
    "repositories": [
        {
            "type": "path",
            "url": "../okta-whatsapp/packages/whatsapp-sdk",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "getokta/whatsapp-sdk": "@dev"
    }
}
```

Then:

```bash
composer require getokta/whatsapp-sdk:@dev
```

### From Packagist (after extraction to `getokta/okta-whatsapp-sdk`)

```bash
composer require getokta/whatsapp-sdk
```

## Quickstart

### Tenant scope (read / write / send)

```php
use Okta\WhatsApp\Client;

$client = new Client(
    baseUrl: 'https://wa.example.com',
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
| `baseUrl` | `string` | _required_ | Root URL of the okta-whatsapp API, e.g. `https://wa.example.com`. |
| `token` | `string` | _required_ | Sanctum personal-access token. Abilities determine which endpoints succeed. |
| `timeout` | `int` | `30` | Request timeout in seconds. |
| `retries` | `int` | `2` | Retry budget for 429 + 5xx responses (exponential backoff, base 250 ms). |
| `httpClient` | `Psr\Http\Client\ClientInterface` | Guzzle | Inject a custom PSR-18 client (testing, alt transports). |

## Error handling

All non-2xx responses raise typed exceptions extending `Okta\WhatsApp\Exceptions\WhatsAppException`:

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

## API documentation

See the platform docs at [`docs/`](../../docs/) (relative to the monorepo root) and the
`scribe`-generated reference under `.scribe/` for full request/response shapes.

## Running the tests

```bash
cd packages/whatsapp-sdk
composer install
vendor/bin/phpunit
```

## License

MIT — see [LICENSE](LICENSE).
