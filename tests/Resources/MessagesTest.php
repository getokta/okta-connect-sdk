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
                    'conversation_id' => 'conv_1',
                    'type' => 'text',
                    'status' => 'queued',
                    'body' => 'Hello',
                ],
            ]),
        ], $history);

        // The server expects the flat shape: channel_id + wa_id + body.
        $message = $client->messages()->send([
            'channel_id' => 'ch_1',
            'wa_id' => '966500000000',
            'type' => 'text',
            'body' => 'Hello',
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

    public function test_send_text_builds_the_flat_server_payload(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'msg_1', 'type' => 'text', 'status' => 'queued']]),
        ], $history);

        $client->messages()->sendText('ch_1', '966500000000', 'Your order is on the way!', idempotencyKey: 'ord-1');

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame('ch_1', $body['channel_id']);
        $this->assertSame('966500000000', $body['wa_id']);
        $this->assertSame('text', $body['type']);
        $this->assertSame('Your order is on the way!', $body['body']);
        // Must NOT emit the WhatsApp-Cloud-style keys the server rejects.
        $this->assertArrayNotHasKey('to', $body);
        $this->assertArrayNotHasKey('text', $body);
        $this->assertSame('ord-1', $history[0]['request']->getHeaderLine('Idempotency-Key'));
    }

    public function test_send_media_builds_the_media_payload(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'msg_2', 'type' => 'image', 'status' => 'queued']]),
        ], $history);

        $client->messages()->sendMedia('ch_1', '966500000000', 'image', 'https://cdn.example.com/a.jpg', 'Look!');

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame('image', $body['type']);
        $this->assertSame('https://cdn.example.com/a.jpg', $body['media_url']);
        $this->assertSame('Look!', $body['body']);
        $this->assertSame('966500000000', $body['wa_id']);
    }

    public function test_reply_targets_an_existing_conversation(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'msg_3', 'type' => 'text', 'status' => 'queued']]),
        ], $history);

        $client->messages()->reply('conv_1', 'Thanks!');

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame('conv_1', $body['conversation_id']);
        $this->assertSame('text', $body['type']);
        $this->assertSame('Thanks!', $body['body']);
        $this->assertArrayNotHasKey('channel_id', $body);
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
