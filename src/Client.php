<?php

declare(strict_types=1);

namespace Okta\WhatsApp;

use Okta\WhatsApp\Http\HttpClient;
use Okta\WhatsApp\Http\HttpClientInterface;
use Okta\WhatsApp\Resources\Channels;
use Okta\WhatsApp\Resources\Contacts;
use Okta\WhatsApp\Resources\Conversations;
use Okta\WhatsApp\Resources\Messages;
use Okta\WhatsApp\Resources\Webhooks;
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
            userAgent: $options['userAgent'] ?? 'okta-whatsapp-sdk-php/0.1',
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

    public function admin(): AdminClient
    {
        return $this->admin ??= new AdminClient($this->http);
    }
}
