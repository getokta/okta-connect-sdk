<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class TicketsTest extends TestCase
{
    public function test_open_posts_and_returns_ticket(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => [
                'id' => 'tk_1', 'number' => 42, 'subject' => 'Broken', 'status' => 'open',
                'stage' => 'New', 'stage_id' => 'st_1',
            ]]),
        ], $history);

        $ticket = $client->tickets()->open(['subject' => 'Broken'], idempotencyKey: 'tk-init');

        $this->assertSame('tk_1', $ticket->id);
        $this->assertSame(42, $ticket->number);
        $this->assertSame('open', $ticket->status);
        $req = $history[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/api/v1/tickets', $req->getUri()->getPath());
        $this->assertSame('tk-init', $req->getHeaderLine('Idempotency-Key'));
    }

    public function test_list_returns_paginated_tickets_with_filters(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'tk_1', 'subject' => 'A'], ['id' => 'tk_2', 'subject' => 'B']],
                'meta' => ['current_page' => 1, 'per_page' => 25, 'total' => 2],
            ]),
        ], $history);

        $result = $client->tickets()->list(['status' => 'open']);

        $this->assertCount(2, $result);
        $this->assertSame('tk_1', $result->items()[0]->id);
        $this->assertStringContainsString('status=open', $history[0]['request']->getUri()->getQuery());
        $this->assertSame('/api/v1/tickets', $history[0]['request']->getUri()->getPath());
    }

    public function test_transition_posts_stage(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'tk_1', 'status' => 'resolved', 'stage' => 'Done']]),
        ], $history);

        $ticket = $client->tickets()->transition('tk_1', ['stage_id' => 'st_9']);

        $this->assertSame('resolved', $ticket->status);
        $this->assertSame('/api/v1/tickets/tk_1/transition', $history[0]['request']->getUri()->getPath());
    }
}
