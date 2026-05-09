<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Message;
use Okta\Connect\WhatsApp\Enums\MessageStatus;
use Okta\Connect\WhatsApp\Enums\MessageType;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class MessagesTest extends TestCase
{
    public function test_send_posts_to_v1_messages_and_returns_dto(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => [
                    'id' => 'msg_1',
                    'channel_id' => 'ch_1',
                    'to' => '+9665',
                    'type' => 'text',
                    'status' => 'queued',
                    'body' => 'Hello',
                ],
            ]),
        ], $history);

        $message = $client->messages()->send([
            'channel_id' => 'ch_1',
            'to' => '+9665',
            'type' => 'text',
            'text' => ['body' => 'Hello'],
        ], idempotencyKey: 'op-1');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('msg_1', $message->id);
        $this->assertSame(MessageType::Text, $message->type);
        $this->assertSame(MessageStatus::Queued, $message->status);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/messages', $request->getUri()->getPath());
        $this->assertSame('op-1', $request->getHeaderLine('Idempotency-Key'));
    }

    public function test_list_with_conversation_id_uses_nested_route(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'm1', 'type' => 'text']],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->messages()->list(['conversation_id' => 'conv_1', 'per_page' => 50]);

        $this->assertCount(1, $page);
        $request = $history[0]['request'];
        $this->assertSame('/api/v1/conversations/conv_1/messages', $request->getUri()->getPath());
        $this->assertStringContainsString('per_page=50', $request->getUri()->getQuery());
    }

    public function test_list_without_conversation_id_uses_flat_route(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->messages()->list();

        $this->assertSame('/api/v1/messages', $history[0]['request']->getUri()->getPath());
    }
}
