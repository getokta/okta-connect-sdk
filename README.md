# Okta Connect PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-alpha-orange)](#)

A framework-agnostic PHP client for the **Okta Connect** omnichannel platform:
WhatsApp messaging, **transactional + bulk email**, **social publishing**
(Telegram / X / Instagram / …) and **broadcast campaigns** — all under one client
surface. No Laravel dependency in the SDK code itself — a separate
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

// Typed helpers build the correct request shape for you:
$client->messages()->sendText('01H...channel', '966500000000', 'Hello');
$client->messages()->sendMedia('01H...channel', '966500000000', 'image', 'https://cdn.example.com/a.jpg', 'Look!');
$client->messages()->reply('01H...conversation', 'Thanks!');

// Or send a raw payload (flat shape: channel_id + wa_id + body, or conversation_id + body):
$client->messages()->send([
    'channel_id' => '01H...',
    'wa_id'      => '966500000000',
    'type'       => 'text',
    'body'       => 'Hello',
]);

$messages      = $client->messages()->list(['conversation_id' => '01H...', 'per_page' => 50]);
$conversations = $client->conversations()->list();
$conversation  = $client->conversations()->get($id);
$contacts      = $client->contacts()->list(['search' => '+966']);
$client->contacts()->upsert(['phone' => '+9665...', 'name' => 'Ali']);
$channels      = $client->channels()->list();
$client->webhooks()->register(['url' => 'https://...', 'events' => ['message.received']]);

// Channels filter by platform type and connection status — `type` takes a
// channel type value (cloud_api / baileys / telegram / instagram_dm /
// twitter / linkedin / tiktok / email) or the family alias `whatsapp`
// (covers cloud_api + baileys); `status` is connected / disconnected /
// pending / failed:
$client->channels()->list(['type' => 'telegram', 'status' => 'connected']);
$client->channels()->listByType('tiktok', 'connected', ['per_page' => 5]);
$client->channels()->whatsapp('connected');       // both WhatsApp flavours
$client->channels()->connected();                 // any platform, connected
$client->channels()->disconnected('instagram_dm');

// Prune stale channels — e.g. old WhatsApp links never scanned. delete()
// needs a `write` (or `admin`) token; it disconnects + emits channel.deleted.
foreach ($client->channels()->awaitingScan('baileys')->items() as $stale) {
    $client->channels()->delete($stale->id);
}

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

### Email

Send transactional email (receipts, OTPs, notifications) as one of your **verified
sending domains** — DKIM-signed server-side — read the send log, and manage
templates, broadcasts and the suppression list. Needs the `send` ability to send.

```php
// Send an email
$email = $client->emails()->send([
    'from'    => 'Acme <hello@mail.acme.com>',   // bare address also accepted
    'to'      => ['ali@example.com'],
    'subject' => 'Your receipt',
    'html'    => '<h1>Thanks!</h1>',
    'text'    => 'Thanks!',                       // at least one of html/text/template
], idempotencyKey: 'order-1042-receipt');

echo $email->status;   // queued | sent | delivered | bounced | complained | failed

// …or render a stored template with variables
$client->emails()->sendTemplate(
    from: 'Acme <hello@mail.acme.com>',
    to: ['ali@example.com'],
    template: 'order-receipt',                    // slug or ULID
    variables: ['order_id' => '1042'],
);

// …or design a branded message in code — HtmlMessageBuilder emits
// email-client-safe HTML (table layout, inlined CSS, 600px centered card;
// Gmail/Outlook-proof). RTL-first: make() defaults to dir="rtl" lang="ar".
use Okta\Connect\WhatsApp\Email\HtmlMessageBuilder;

$message = HtmlMessageBuilder::make()             // make(false) ⇒ LTR
    ->brandColor('#10b981')
    ->logo('https://cdn.acme.com/logo.png')
    ->preheader('طلبك في الطريق')                 // hidden inbox preview text
    ->heading('شكراً لطلبك!')
    ->paragraph('طلبك رقم 1042 قيد التجهيز الآن.')
    ->button('تتبع الطلب', 'https://acme.com/orders/1042')
    ->divider()
    ->footer('© 2026 Acme — جميع الحقوق محفوظة');

// sendHtml() accepts the builder directly (any Stringable) and a bare
// string recipient; both are normalised for you:
$client->emails()->sendHtml(
    'Acme <hello@mail.acme.com>',
    'ali@example.com',
    'طلبك رقم 1042',
    $message,
);

// Send log + a single message
$sent = $client->emails()->list(['status' => 'delivered', 'per_page' => 50]);
$one  = $client->emails()->get('01H...');

// Delivery analytics (defaults to the last 30 days)
$stats = $client->emails()->analytics(from: '2026-06-01', to: '2026-06-30');
echo $stats->summary['delivery_rate'];

// Reusable templates
$tpl = $client->emails()->templates()->create([
    'name'    => 'Order receipt',
    'subject' => 'Your order {{ order_id }} is confirmed',
    'html'    => '<p>Hi {{ name }}, order {{ order_id }} is on the way.</p>',
]);
$client->emails()->templates()->update($tpl->id, ['subject' => 'Order {{ order_id }} shipped']);

// Bulk broadcasts to a CRM-tag audience
$bc = $client->emails()->broadcasts()->create([
    'name'     => 'July newsletter',
    'from'     => 'Acme <hello@mail.acme.com>',
    'subject'  => "What's new in July",
    'html'     => '<h1>Hello!</h1>',
    'audience' => ['tag_slugs' => ['newsletter']],   // omit ⇒ everyone with an email
]);
$client->emails()->broadcasts()->queue($bc->id);      // fan out one send per recipient

// Suppression list (bounces/complaints are added automatically; you can add manually)
$client->emails()->suppressions()->add('bounced@example.com');
$client->emails()->suppressions()->remove('bounced@example.com');
```

> **Bring your own SMTP.** A domain connected via your own SMTP/provider
> (SES / Postmark / Resend) is usable immediately — no DNS verification needed;
> your server authenticates the mail. Platform-managed domains still publish
> SPF/DKIM/DMARC first.

### Social publishing

Compose a post once and fan it out to one or more social channels. With a future
`scheduledAt` the post is scheduled; without one it's a draft.

```php
$post = $client->socialPosts()->schedule(
    text: 'New drop is live! 🎉',
    channelIds: ['01H...x', '01H...telegram'],
    scheduledAt: '2026-07-20T09:00:00+00:00',
    media: [['url' => 'https://cdn.example.com/promo.jpg', 'type' => 'image']],
);

$client->socialPosts()->draft('Behind the scenes…', ['01H...instagram']);

// Read each platform's outcome — including the upstream failure reason
foreach ($client->socialPosts()->get($post->id)->targets as $t) {
    echo "{$t->status} → {$t->permalink}\n";
}
```

### Campaigns

Broadcast (bulk / drip) message campaigns: create a draft, then queue it to
materialise the audience and start sending.

```php
$campaign = $client->campaigns()->create([
    'name'            => 'Ramadan promo',
    'channel_id'      => '01H...channel',
    'template_id'     => '01H...template',
    'audience_filter' => ['tag_slugs' => ['vip']],
]);
$client->campaigns()->queue($campaign->id);
```

> **Platform-admin surface is not shipped in this public SDK.** Privileged
> `platform.admin` operations (workspace/organization provisioning, token
> minting, embed-secret provisioning) are performed server-side by the
> platform operator and are intentionally excluded from this developer SDK
> to keep the public attack surface minimal. Call those endpoints directly
> from a trusted backend if you operate the platform.

### Connecting an account (OAuth-style, one click)

The easy way to get a token for a user's organization — no copy-pasting API
keys. Send the user to the consent screen, then swap the returned one-time code
for a token. No `client_secret`, no PKCE: the code is single-use, expires in
5 minutes, and is bound to your `redirect_uri`.

```php
use Okta\Connect\WhatsApp\Client;

$connect     = Client::connect('https://connect.getokta.io'); // no token yet
$redirectUri = 'https://crm.example.com/oktawa/callback';

// 1) Redirect the user to the consent screen. Keep `state` in the session.
$state = \Okta\Connect\WhatsApp\Connect\Connect::generateState();
$_SESSION['okta_state'] = $state;

$url = $connect->authorizationUrl(
    appName:     'My CRM',
    redirectUri: $redirectUri,
    abilities:   ['read', 'send'],   // subset of read/write/send/webhooks/admin
    state:       $state,
    logoUrl:     'https://cdn.my-crm.com/logo.png', // optional — shown on consent (https only)
);
// header('Location: '.$url);

// 2) On the callback, verify state and exchange the code in one call:
$token  = $connect->handleCallback($_GET, $redirectUri, $_SESSION['okta_state']);

// 3) You now have a ready-to-use token — build a client and go.
$client = new Client('https://connect.getokta.io', $token->accessToken);

$token->abilities;          // ['read', 'send'] — what the user granted
$token->can('send');        // true
$token->expiresAt;          // ISO-8601 string, or null
```

`handleCallback()` throws a `WhatsAppException` when the user denied consent
(`?error=access_denied`), the `state` doesn't match (CSRF), or no code is
present. Prefer it over calling `exchange($code, $redirectUri)` directly so the
security checks always run.

**Checking your grant.** Introspect what the token may do, and ask for more when
you need it — the consent screen highlights the new permissions and the exchange
replaces the old, narrower grant:

```php
$conn = $client->connection();          // DTO\Connection
$conn->abilities;                       // ['read', 'send']
$conn->logoUrl;                         // the app logo you passed at connect (or its favicon)
$conn->can('admin');                    // false
$missing = $conn->missing(['read', 'admin']);   // ['admin']

if ($missing !== []) {
    // Send the user back through Connect with the fuller ability set.
    $url = Client::connect($baseUrl)->authorizationUrl(
        appName: 'My CRM', redirectUri: $redirectUri,
        abilities: ['read', 'send', 'admin'], state: $state,
    );
}
```

**Disconnecting.** Your app can sever its own link at any time — the token is
revoked and the workspace is notified via a `connection.revoked` webhook:

```php
$client->revokeConnection();   // true; every later call with this token 401s
```

The workspace can also unlink your app from its dashboard, which fires the same
`connection.revoked` event (with `source: "workspace"`) to any webhook you
registered — subscribe to it to react when access is pulled.

### Embedding the inbox (iframe)

Mint embed tokens and build iframe URLs natively — no hand-rolled JWTs. Obtain the
shared secret from your platform operator (provisioned server-side), then:

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

### Webhooks

Register outbound webhooks over the API instead of adding them by hand in the
dashboard. The signing `secret` is returned **once** on create — store it.

A token needs the `webhooks` ability (a least-privilege scope for exactly this),
or the broader `write` / `admin` scope. Request just `['read', 'webhooks']` when
your app only manages webhooks and shouldn't touch anything else.

```php
use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use Okta\Connect\WhatsApp\Resources\Webhooks;

$hook = $client->webhooks()->create([
    'name'   => 'Lifecycle',
    'url'    => 'https://example.test/hooks/okta',
    'events' => [
        WebhookEvent::SubscriptionExpired->value,   // subscription ended
        WebhookEvent::SubscriptionCancelled->value, // cancelled
        WebhookEvent::ChannelDeleted->value,        // a channel was removed
        WebhookEvent::ChannelDisconnected->value,   // …or disconnected
    ],
    // 'events' => [WebhookEvent::All->value],       // or receive everything
]);

$secret = $hook->secret; // shown ONCE — persist it now

$client->webhooks()->list();          // PaginatedResult<Webhook> (no secret)
$client->webhooks()->delete($hook->id);
```

Verify inbound deliveries before trusting them — the platform signs the raw body
with your secret and sends it in `X-Okta-Signature: sha256=<hmac>`:

```php
$ok = Webhooks::verifySignature(
    rawBody: file_get_contents('php://input'),
    signatureHeader: $_SERVER['HTTP_X_OKTA_SIGNATURE'] ?? '',
    secret: $secret,
);
```

Or **verify + decode in one step** into a typed `WebhookNotification` and branch on
the event. Message events carry which conversation + channel they belong to and
whether they're a reply:

```php
use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use Okta\Connect\WhatsApp\Resources\Webhooks;

$hook = Webhooks::parse(
    rawBody: file_get_contents('php://input'),
    signatureHeader: $_SERVER['HTTP_X_OKTA_SIGNATURE'] ?? '',
    secret: $secret,                                 // throws on a bad signature
);

match ($hook->type()) {
    WebhookEvent::MessageSent, WebhookEvent::MessageReceived => handleMessage(
        conversation: $hook->conversationId(),   // which conversation
        channel:      $hook->channelType(),      // which channel
        body:         $hook->messageBody(),
        isReply:      $hook->isReply(),          // …and whether it's a reply
    ),
    WebhookEvent::MessageDelivered, WebhookEvent::MessageRead => updateReceipt($hook),
    WebhookEvent::ChannelDeleted   => teardown($hook->get('channel.id')),
    default => null,
};
// $hook->get('any.dotted.path') reads anything from the event payload.
```

Prefer a **router** over a `match`, and typed per-family views over raw arrays:

```php
use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use Okta\Connect\WhatsApp\Webhook\WebhookRouter;

(new WebhookRouter($secret))                     // verifies the signature
    ->on(WebhookEvent::MessageReceived, fn ($h) => reply($h->message()->conversationId()))
    ->onMessage(fn ($h) => log($h->message()->status()))   // any message.*
    ->onChannel(fn ($h) => sync($h->channel()->channelId()))
    ->onSubscription(fn ($h) => billing($h->subscription()->status()))
    ->onAny(fn ($h) => audit($h))                          // fallback
    ->dispatch(file_get_contents('php://input'), $_SERVER['HTTP_X_OKTA_SIGNATURE'] ?? '');
```

`$hook->message()`, `->channel()`, `->subscription()` return typed views
(`MessageEvent`/`ChannelEvent`/`SubscriptionEvent`) — or `null` for a different
family.

### More resources — tickets, tags, analytics

```php
// Support tickets
$ticket = $client->tickets()->open(['subject' => 'Order stuck', 'contact_id' => $contactUlid]);
$client->tickets()->transition($ticket->id, ['stage_id' => $resolvedStageUlid]);
$client->tickets()->list(['status' => 'open']);   // PaginatedResult<Ticket>

// CRM tags — apply slugs to a contact (unknown slugs are created)
$client->tags()->applyToContact($contactUlid, ['vip', 'lead']);
$client->tags()->list();                           // PaginatedResult<Tag>

// Read-only analytics — aggregate totals over a date range
$m = $client->analytics()->metrics(['from' => '2026-06-01', 'to' => '2026-06-30', 'platform' => 'whatsapp']);
$m->metric('messages.inbound');                    // 120
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
