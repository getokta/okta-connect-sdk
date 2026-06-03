<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\TransactionalMessage;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class AdminMessagesTest extends TestCase
{
    public function test_transactional_posts_to_admin_endpoint_and_returns_dto(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(202, [
                'id' => 'msg_01HABC',
                'status' => 'queued',
                'to' => '+966500000000',
                'channel_id' => '01HCHAN',
                'created_at' => '2026-06-03T00:00:00+00:00',
            ]),
        ], $history);

        $message = $client->admin()->messages()->transactional([
            'to' => '+966500000000',
            'type' => 'text',
            'text' => 'Your order #1234 has shipped.',
        ], idempotencyKey: 'txn-1');

        $this->assertInstanceOf(TransactionalMessage::class, $message);
        $this->assertSame('msg_01HABC', $message->id);
        $this->assertSame('queued', $message->status);
        $this->assertSame('+966500000000', $message->to);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/admin/messages/transactional', $request->getUri()->getPath());
        $this->assertSame('txn-1', $request->getHeaderLine('Idempotency-Key'));
    }

    public function test_otp_posts_to_admin_endpoint(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(202, [
                'id' => 'msg_01HOTP',
                'status' => 'queued',
                'to' => '+966500000000',
                'channel_id' => '01HCHAN',
                'created_at' => '2026-06-03T00:00:00+00:00',
            ]),
        ], $history);

        $message = $client->admin()->messages()->otp([
            'to' => '+966500000000',
            'code' => '482915',
            'ttl_seconds' => 300,
            'purpose' => 'two_factor',
            'locale' => 'ar',
        ]);

        $this->assertSame('msg_01HOTP', $message->id);
        $this->assertSame('/api/v1/admin/messages/otp', $history[0]['request']->getUri()->getPath());
        $this->assertSame('POST', $history[0]['request']->getMethod());
    }
}
