<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp;

use Okta\Connect\WhatsApp\Connect\Connect;
use Okta\Connect\WhatsApp\Embed\Embed;
use Okta\Connect\WhatsApp\Http\HttpClient;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;
use Okta\Connect\WhatsApp\Resources\Analytics;
use Okta\Connect\WhatsApp\Resources\Campaigns;
use Okta\Connect\WhatsApp\Resources\Channels;
use Okta\Connect\WhatsApp\Resources\Contacts;
use Okta\Connect\WhatsApp\Resources\Conversations;
use Okta\Connect\WhatsApp\Resources\Emails;
use Okta\Connect\WhatsApp\Resources\Groups;
use Okta\Connect\WhatsApp\Resources\Integrations\Meta;
use Okta\Connect\WhatsApp\Resources\Integrations\QrPairing;
use Okta\Connect\WhatsApp\Resources\Messages;
use Okta\Connect\WhatsApp\Resources\SocialPosts;
use Okta\Connect\WhatsApp\Resources\Tags;
use Okta\Connect\WhatsApp\Resources\Templates;
use Okta\Connect\WhatsApp\Resources\Tickets;
use Okta\Connect\WhatsApp\Resources\Webhooks;
use Psr\Http\Client\ClientInterface;

/**
 * Top-level entry point for the SDK.
 *
 * Construct with credentials + transport options; access resources via
 * the named accessor methods. Every accessor is lazy and idempotent so
 * you can keep a single Client around for the lifetime of your app.
 */
final class Client
{
    private readonly HttpClientInterface $http;

    private readonly string $baseUrl;

    private ?Messages $messages = null;
    private ?Conversations $conversations = null;
    private ?Contacts $contacts = null;
    private ?Channels $channels = null;
    private ?Webhooks $webhooks = null;
    private ?Templates $templates = null;
    private ?Meta $meta = null;
    private ?QrPairing $qr = null;
    private ?Groups $groups = null;
    private ?Emails $emails = null;
    private ?SocialPosts $socialPosts = null;
    private ?Campaigns $campaigns = null;
    private ?Tickets $tickets = null;
    private ?Tags $tags = null;
    private ?Analytics $analytics = null;

    /**
     * @param  array{timeout?: int, retries?: int, httpClient?: ClientInterface, userAgent?: string}  $options
     */
    public function __construct(
        string $baseUrl,
        string $token,
        array $options = [],
        ?HttpClientInterface $httpClient = null,
    ) {
        $config = new Config(
            baseUrl: $baseUrl,
            token: $token,
            timeout: $options['timeout'] ?? 30,
            retries: $options['retries'] ?? 2,
            httpClient: $options['httpClient'] ?? null,
            userAgent: $options['userAgent'] ?? 'okta-connect-sdk-php/0.9',
        );

        $this->http = $httpClient ?? new HttpClient($config);
        $this->baseUrl = $baseUrl;
    }

    /**
     * Start the OAuth-style "connect with one click" flow — no token needed
     * yet, since this is how you obtain one. Build the consent URL, then
     * exchange the returned code for an access token:
     *
     *   $connect = Client::connect('https://connect.getokta.io');
     *   $url     = $connect->authorizationUrl('My CRM', $redirectUri, ['read', 'send'], $state);
     *   // ...user authorizes, returns to $redirectUri with ?code=&state=
     *   $token   = $connect->handleCallback($request->query(), $redirectUri, $state);
     *   $client  = new Client('https://connect.getokta.io', $token->accessToken);
     */
    public static function connect(string $baseUrl): Connect
    {
        return new Connect($baseUrl);
    }

    /**
     * Build a Client around a fully-formed Config (advanced usage).
     */
    public static function fromConfig(Config $config, ?HttpClientInterface $httpClient = null): self
    {
        $client = new self($config->baseUrl, $config->token, [
            'timeout' => $config->timeout,
            'retries' => $config->retries,
            'httpClient' => $config->httpClient,
            'userAgent' => $config->userAgent,
        ], $httpClient);

        return $client;
    }

    public function http(): HttpClientInterface
    {
        return $this->http;
    }

    public function messages(): Messages
    {
        return $this->messages ??= new Messages($this->http);
    }

    public function conversations(): Conversations
    {
        return $this->conversations ??= new Conversations($this->http);
    }

    public function contacts(): Contacts
    {
        return $this->contacts ??= new Contacts($this->http);
    }

    public function channels(): Channels
    {
        return $this->channels ??= new Channels($this->http);
    }

    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks($this->http);
    }

    /**
     * Meta message templates — list the catalogue + send an approved
     * template to a wa_id.
     */
    public function templates(): Templates
    {
        return $this->templates ??= new Templates($this->http);
    }

    /**
     * WhatsApp Embedded Signup (Meta) — driven natively from the
     * partner UI, no platform iframe required.
     */
    public function meta(): Meta
    {
        return $this->meta ??= new Meta($this->http);
    }

    /**
     * QR pairing flow — companion to Meta Embedded Signup for
     * businesses that don't have a Cloud-API setup.
     */
    public function qr(): QrPairing
    {
        return $this->qr ??= new QrPairing($this->http);
    }

    /**
     * WhatsApp groups (Baileys-only). List/get + create/rename/
     * add/remove members + change picture + force resync.
     */
    public function groups(): Groups
    {
        return $this->groups ??= new Groups($this->http);
    }

    /**
     * Transactional + bulk email — send a message as one of your verified
     * sending domains, read the send log, and manage templates, broadcasts
     * and the suppression list via nested accessors:
     *
     *   $client->emails()->send([...]);
     *   $client->emails()->templates()->create([...]);
     *   $client->emails()->broadcasts()->queue($ulid);
     *   $client->emails()->suppressions()->add('bounced@example.com');
     */
    public function emails(): Emails
    {
        return $this->emails ??= new Emails($this->http);
    }

    /**
     * Social publishing — compose a post and fan it out to one or more social
     * channels (Telegram, X, Instagram, …). Schedule for later or draft now.
     */
    public function socialPosts(): SocialPosts
    {
        return $this->socialPosts ??= new SocialPosts($this->http);
    }

    /**
     * Broadcast messaging campaigns — create a draft, then queue it to
     * materialise its audience and start sending.
     */
    public function campaigns(): Campaigns
    {
        return $this->campaigns ??= new Campaigns($this->http);
    }

    /**
     * Support tickets — open, transition across pipeline stages, and read the
     * queue.
     */
    public function tickets(): Tickets
    {
        return $this->tickets ??= new Tickets($this->http);
    }

    /**
     * CRM tags — list the org's tags and apply tag slugs to a contact.
     */
    public function tags(): Tags
    {
        return $this->tags ??= new Tags($this->http);
    }

    /**
     * Read-only analytics — aggregate metric totals over a date range across
     * conversations + social.
     */
    public function analytics(): Analytics
    {
        return $this->analytics ??= new Analytics($this->http);
    }

    /**
     * Disconnect this connection: revoke the access token this client is
     * using (POST /api/v1/oauth/revoke). Afterwards every call with this token
     * gets 401, and the workspace is notified via a `connection.revoked`
     * webhook. Idempotent. Returns true on success.
     *
     * Use this to let your app "unlink" itself from a workspace it connected to
     * through the Connect (unified login) flow.
     */
    public function revokeConnection(): bool
    {
        $response = $this->http->post('/api/v1/oauth/revoke');

        return ($response->json()['revoked'] ?? false) === true;
    }

    /**
     * Embed integration surface — mint SSO / cookieless tokens and build
     * iframe URLs for the embedded inbox. Obtain the shared secret from
     * your platform operator (provisioned server-side under `embed.*`
     * settings — the privileged admin surface is not shipped in this SDK),
     * then:
     *
     *   $embed = $client->embed($secret);
     *   $url   = $embed->inboxUrl(new EmbedUser('u-1', 'op@acme.com', 'Op'));
     *
     * The Client's configured base URL is reused, so callers never
     * re-type the platform host. Override `issuer`/`audience` only when a
     * provisioned per-partner secret uses a non-default issuer.
     */
    public function embed(string $sharedSecret, string $issuer = 'okta-web', string $audience = 'okta-whatsapp'): Embed
    {
        return new Embed($this->baseUrl, $sharedSecret, $issuer, $audience);
    }
}
