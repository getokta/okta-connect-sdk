<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp;

use Okta\Connect\WhatsApp\Http\HttpClient;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;
use Okta\Connect\WhatsApp\Resources\Channels;
use Okta\Connect\WhatsApp\Resources\Contacts;
use Okta\Connect\WhatsApp\Resources\Conversations;
use Okta\Connect\WhatsApp\Resources\Groups;
use Okta\Connect\WhatsApp\Resources\Integrations\Meta;
use Okta\Connect\WhatsApp\Resources\Integrations\QrPairing;
use Okta\Connect\WhatsApp\Resources\Messages;
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

    private ?Messages $messages = null;
    private ?Conversations $conversations = null;
    private ?Contacts $contacts = null;
    private ?Channels $channels = null;
    private ?Webhooks $webhooks = null;
    private ?Meta $meta = null;
    private ?QrPairing $qr = null;
    private ?Groups $groups = null;
    private ?AdminClient $admin = null;

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
            userAgent: $options['userAgent'] ?? 'okta-connect-sdk-php/0.4',
        );

        $this->http = $httpClient ?? new HttpClient($config);
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

    public function admin(): AdminClient
    {
        return $this->admin ??= new AdminClient($this->http);
    }
}
