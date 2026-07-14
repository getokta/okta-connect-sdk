<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use Okta\Connect\WhatsApp\Resources\Webhooks;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class WebhooksTest extends TestCase
{
    public function test_register_posts_payload_and_returns_record(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'wh_1', 'url' => 'https://example.test']]),
        ], $history);

        $result = $client->webhooks()->register([
            'url' => 'https://example.test',
            'events' => ['message.received'],
        ], idempotencyKey: 'wh-init');

        $this->assertSame('wh_1', $result['id']);
        $request = $history[0]['request'];
        $this->assertSame('/api/v1/webhooks', $request->getUri()->getPath());
        $this->assertSame('wh-init', $request->getHeaderLine('Idempotency-Key'));
    }

    public function test_create_returns_webhook_dto_with_secret(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => [
                'id' => 'wh_2',
                'name' => 'Lifecycle',
                'url' => 'https://example.test/hooks',
                'events' => ['subscription.expired', 'channel.deleted'],
                'is_active' => true,
                'max_attempts' => 5,
                'secret' => 'shown-once-secret',
            ]]),
        ], $history);

        $webhook = $client->webhooks()->create([
            'name' => 'Lifecycle',
            'url' => 'https://example.test/hooks',
            'events' => [WebhookEvent::SubscriptionExpired->value, WebhookEvent::ChannelDeleted->value],
        ]);

        $this->assertSame('wh_2', $webhook->id);
        $this->assertSame(['subscription.expired', 'channel.deleted'], $webhook->events);
        $this->assertTrue($webhook->isActive);
        $this->assertSame('shown-once-secret', $webhook->secret);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/webhooks', $request->getUri()->getPath());
    }

    public function test_list_returns_paginated_webhooks(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => 'wh_1', 'url' => 'https://a.test', 'events' => ['*']],
                    ['id' => 'wh_2', 'url' => 'https://b.test', 'events' => ['channel.connected']],
                ],
                'meta' => ['current_page' => 1, 'per_page' => 25, 'total' => 2],
            ]),
        ], $history);

        $result = $client->webhooks()->list(['per_page' => 25]);

        $this->assertCount(2, $result);
        $this->assertSame('wh_1', $result->items()[0]->id);
        // The list response carries no secret.
        $this->assertNull($result->items()[0]->secret);
        $this->assertSame('/api/v1/webhooks', $history[0]['request']->getUri()->getPath());
    }

    public function test_delete_returns_true_on_success(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['deleted' => true]),
        ], $history);

        $this->assertTrue($client->webhooks()->delete('wh_9'));

        $request = $history[0]['request'];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/api/v1/webhooks/wh_9', $request->getUri()->getPath());
    }

    public function test_verify_signature_accepts_a_valid_signature(): void
    {
        $secret = 'top-secret';
        $body = '{"event":"subscription.expired","organization_id":1}';
        $header = 'sha256='.hash_hmac('sha256', $body, $secret);

        $this->assertTrue(Webhooks::verifySignature($body, $header, $secret));
        $this->assertFalse(Webhooks::verifySignature($body, $header, 'wrong-secret'));
        $this->assertFalse(Webhooks::verifySignature($body, 'sha256=deadbeef', $secret));
        $this->assertFalse(Webhooks::verifySignature($body.'tampered', $header, $secret));
    }
}
