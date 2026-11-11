<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\MessageSender;
use Okta\Connect\WhatsApp\Resources\Members;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class MembersTest extends TestCase
{
    public function test_upsert_puts_the_member_and_says_whether_it_was_created(): void
    {
        $history = [];
        $member = ['id' => 'usr_1', 'external_id' => 'emp-41', 'name' => 'Sara', 'email' => null, 'role' => 'agent', 'status' => 'active'];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => $member]),
            ResponseFactory::json(200, ['data' => $member]),
        ], $history);

        $first = $client->members()->upsert(['external_id' => 'emp-41', 'name' => 'Sara']);
        $again = $client->members()->upsert(['external_id' => 'emp-41', 'name' => 'Sara']);

        $this->assertTrue($first->created);
        $this->assertFalse($again->created);
        $this->assertSame('emp-41', $first->member->externalId);
        $this->assertNull($first->member->email);
        $this->assertTrue($first->member->isActive());
        $this->assertSame('ext:emp-41', $first->member->senderReference());

        $request = $history[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/api/v1/members', $request->getUri()->getPath());
        $this->assertSame(['external_id' => 'emp-41', 'name' => 'Sara'], json_decode((string) $request->getBody(), true));
    }

    public function test_a_member_is_addressed_by_your_own_id(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'usr_1', 'external_id' => 'staff/9', 'status' => 'suspended']]),
            ResponseFactory::json(204, []),
        ], $history);

        $member = $client->members()->update(Members::external('staff/9'), ['status' => 'suspended']);
        $removed = $client->members()->remove(Members::external('staff/9'));

        $this->assertFalse($member->isActive());
        $this->assertTrue($removed);
        $this->assertSame('PATCH', $history[0]['request']->getMethod());
        $this->assertSame('/api/v1/members/ext%3Astaff/9', $history[0]['request']->getUri()->getPath());
        $this->assertSame('DELETE', $history[1]['request']->getMethod());
    }

    public function test_a_member_without_your_id_is_referenced_by_ours(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'usr_7', 'external_id' => null]]),
        ]);

        $this->assertSame('usr_7', $client->members()->get('usr_7')->senderReference());
    }

    public function test_a_message_is_sent_as_a_member_and_comes_back_with_its_sender(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => [
                'id' => 'msg_1',
                'type' => 'text',
                'status' => 'queued',
                'sender' => ['id' => 'usr_1', 'external_id' => 'emp-41', 'name' => 'Sara', 'email' => null],
            ]]),
            ResponseFactory::json(201, ['data' => ['id' => 'msg_2', 'type' => 'text', 'status' => 'queued']]),
        ], $history);

        $message = $client->messages()->sendText('ch_1', '966500000000', 'Hello', sender: 'ext:emp-41');
        $unsigned = $client->messages()->sendText('ch_1', '966500000000', 'Hello');

        $this->assertInstanceOf(MessageSender::class, $message->sender);
        $this->assertSame('emp-41', $message->sender->externalId);
        $this->assertSame('Sara', $message->sender->name);
        // Still where it was before it had a type.
        $this->assertSame('usr_1', $message->extra['sender']['id']);
        $this->assertNull($unsigned->sender);

        $this->assertSame('ext:emp-41', json_decode((string) $history[0]['request']->getBody(), true)['sender']);
        $this->assertArrayNotHasKey('sender', json_decode((string) $history[1]['request']->getBody(), true));
    }

    public function test_a_reply_and_a_media_message_carry_the_sender_too(): void
    {
        $history = [];
        $ok = ResponseFactory::json(201, ['data' => ['id' => 'm', 'type' => 'text', 'status' => 'queued']]);
        $client = ResponseFactory::makeClient([$ok, $ok], $history);

        $client->messages()->reply('conv_1', 'On it', sender: 'usr_1');
        $client->messages()->sendMedia('ch_1', '966500000000', 'image', 'https://example.com/a.jpg', sender: 'usr_1');

        $this->assertSame('usr_1', json_decode((string) $history[0]['request']->getBody(), true)['sender']);
        $this->assertSame('usr_1', json_decode((string) $history[1]['request']->getBody(), true)['sender']);
    }
}
