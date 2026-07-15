<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests;

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

    public function test_revoke_connection_posts_to_oauth_revoke_and_returns_true(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['revoked' => true]),
        ], $history);

        $ok = $client->revokeConnection();

        $this->assertTrue($ok);
        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/oauth/revoke', $request->getUri()->getPath());
    }

    public function test_connection_introspects_the_granted_abilities(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => [
                'app_name' => 'Frameo',
                'logo_url' => 'https://cdn.frameo.net/logo.png',
                'abilities' => ['read', 'send'],
                'organization' => ['id' => 'org_1', 'name' => 'Acme'],
                'expires_at' => '2026-10-01T00:00:00+00:00',
            ]]),
        ], $history);

        $conn = $client->connection();

        $this->assertSame('/api/v1/oauth/introspect', $history[0]['request']->getUri()->getPath());
        $this->assertSame('Frameo', $conn->appName);
        $this->assertSame('https://cdn.frameo.net/logo.png', $conn->logoUrl);
        $this->assertSame(['read', 'send'], $conn->abilities);
        $this->assertSame('org_1', $conn->organizationId);
        $this->assertTrue($conn->can('send'));
        $this->assertFalse($conn->can('admin'));
        $this->assertSame(['admin'], $conn->missing(['read', 'admin']));
    }

    public function test_can_checks_a_single_ability_via_introspection(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['app_name' => 'X', 'abilities' => ['read']]]),
        ]);

        $this->assertTrue($client->can('read'));
    }
}
