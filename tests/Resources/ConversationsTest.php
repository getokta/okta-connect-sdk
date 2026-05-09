<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Conversation;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ConversationsTest extends TestCase
{
    public function test_list_returns_paginated_conversations(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => 'c1', 'channel_id' => 'ch_1', 'status' => 'open'],
                    ['id' => 'c2', 'channel_id' => 'ch_1', 'status' => 'closed'],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 2],
            ]),
        ]);

        $page = $client->conversations()->list();

        $this->assertCount(2, $page);
        $this->assertContainsOnlyInstancesOf(Conversation::class, iterator_to_array($page));
        $this->assertTrue($page->hasMore());
    }

    public function test_get_returns_single_conversation(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'c1', 'channel_id' => 'ch_1']]),
        ], $history);

        $conv = $client->conversations()->get('c1');

        $this->assertSame('c1', $conv->id);
        $this->assertSame('/api/v1/conversations/c1', $history[0]['request']->getUri()->getPath());
    }

    public function test_messages_hits_nested_route(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->conversations()->messages('c1');

        $this->assertSame('/api/v1/conversations/c1/messages', $history[0]['request']->getUri()->getPath());
    }
}
