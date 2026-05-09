<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

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
}
