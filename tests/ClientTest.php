<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests;

use Okta\Connect\WhatsApp\AdminClient;
use Okta\Connect\WhatsApp\Client;
use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\Resources\Channels;
use Okta\Connect\WhatsApp\Resources\Contacts;
use Okta\Connect\WhatsApp\Resources\Conversations;
use Okta\Connect\WhatsApp\Resources\Messages;
use Okta\Connect\WhatsApp\Resources\Webhooks;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function test_resources_are_lazy_and_memoised(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([], $history);

        $this->assertInstanceOf(Messages::class, $client->messages());
        $this->assertSame($client->messages(), $client->messages());

        $this->assertInstanceOf(Conversations::class, $client->conversations());
        $this->assertInstanceOf(Contacts::class, $client->contacts());
        $this->assertInstanceOf(Channels::class, $client->channels());
        $this->assertInstanceOf(Webhooks::class, $client->webhooks());
        $this->assertInstanceOf(AdminClient::class, $client->admin());
        $this->assertSame($client->admin(), $client->admin());
    }

    public function test_config_trims_trailing_slash(): void
    {
        $config = new Config(baseUrl: 'https://wa.example.com/', token: 't');

        $this->assertSame('https://wa.example.com', $config->baseUrl());
    }

    public function test_constructor_uses_default_options(): void
    {
        $client = new Client('https://wa.example.com', 'tok');

        $this->assertInstanceOf(Messages::class, $client->messages());
    }
}
