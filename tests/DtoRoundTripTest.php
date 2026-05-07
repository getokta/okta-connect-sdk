<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Tests;

use Okta\WhatsApp\DTO\Channel;
use Okta\WhatsApp\DTO\Contact;
use Okta\WhatsApp\DTO\Conversation;
use Okta\WhatsApp\DTO\Message;
use Okta\WhatsApp\DTO\PaginatedResult;
use Okta\WhatsApp\DTO\Workspace;
use Okta\WhatsApp\DTO\WorkspaceToken;
use Okta\WhatsApp\DTO\WorkspaceUser;
use PHPUnit\Framework\TestCase;

final class DtoRoundTripTest extends TestCase
{
    public function test_message_round_trip_preserves_known_fields(): void
    {
        $payload = [
            'id' => 'm_1',
            'conversation_id' => 'c_1',
            'channel_id' => 'ch_1',
            'to' => '+9665',
            'from' => '+9667',
            'type' => 'text',
            'status' => 'delivered',
            'body' => 'Hi',
            'created_at' => '2026-01-01T00:00:00Z',
        ];

        $dto = Message::fromArray($payload);
        $this->assertSame($payload, $dto->toArray());
    }

    public function test_message_round_trip_preserves_extra_fields(): void
    {
        $payload = ['id' => 'm_1', 'type' => 'text', 'metadata' => ['x' => 1]];
        $dto = Message::fromArray($payload);

        $this->assertSame(['x' => 1], $dto->extra['metadata']);
        $this->assertSame(['x' => 1], $dto->toArray()['metadata']);
    }

    public function test_conversation_round_trip(): void
    {
        $payload = ['id' => 'c_1', 'channel_id' => 'ch_1', 'status' => 'open', 'created_at' => '2026-01-01T00:00:00Z'];
        $this->assertSame($payload, Conversation::fromArray($payload)->toArray());
    }

    public function test_contact_round_trip(): void
    {
        $payload = ['id' => 'co_1', 'phone' => '+9665', 'name' => 'Ali', 'email' => 'a@b.com', 'created_at' => 'now'];
        $this->assertSame($payload, Contact::fromArray($payload)->toArray());
    }

    public function test_channel_round_trip(): void
    {
        $payload = ['id' => 'ch_1', 'display_name' => 'Main', 'type' => 'whatsapp_cloud', 'status' => 'active', 'created_at' => 'now'];
        $this->assertSame($payload, Channel::fromArray($payload)->toArray());
    }

    public function test_workspace_round_trip(): void
    {
        $payload = ['id' => '01H', 'name' => 'Acme', 'slug' => 'acme', 'display_name' => 'Acme Inc', 'created_at' => 'now'];
        $this->assertSame($payload, Workspace::fromArray($payload)->toArray());
    }

    public function test_workspace_user_round_trip(): void
    {
        $payload = ['id' => 'u_1', 'workspace_id' => '01H', 'name' => 'Ali', 'email' => 'a@b.com', 'role' => 'admin', 'created_at' => 'now'];
        $this->assertSame($payload, WorkspaceUser::fromArray($payload)->toArray());
    }

    public function test_workspace_token_carries_abilities_and_plaintext(): void
    {
        $payload = [
            'id' => 't_1',
            'workspace_id' => '01H',
            'name' => 'partner-app',
            'abilities' => ['read', 'send'],
            'plain_text_token' => 'abc.def',
            'created_at' => 'now',
        ];

        $dto = WorkspaceToken::fromArray($payload);
        $out = $dto->toArray();

        $this->assertSame(['read', 'send'], $out['abilities']);
        $this->assertSame('abc.def', $out['plain_text_token']);
    }

    public function test_paginated_result_iterates_and_reports_cursor(): void
    {
        $page = PaginatedResult::fromArray(
            [
                'data' => [['id' => 'a'], ['id' => 'b']],
                'links' => ['next' => 'https://wa.example.com/api/v1/contacts?cursor=xyz'],
                'meta' => ['next_cursor' => 'xyz'],
            ],
            static fn (array $row): array => $row,
        );

        $this->assertCount(2, $page);
        $this->assertSame('xyz', $page->nextCursor());
        $this->assertTrue($page->hasMore());
        $this->assertSame([['id' => 'a'], ['id' => 'b']], iterator_to_array($page));
    }

    public function test_paginated_result_has_more_via_page_meta(): void
    {
        $page = PaginatedResult::fromArray(
            ['data' => [['id' => 'a']], 'meta' => ['current_page' => 1, 'last_page' => 3]],
            static fn (array $row): array => $row,
        );

        $this->assertTrue($page->hasMore());
        $this->assertNull($page->nextCursor());
    }
}
