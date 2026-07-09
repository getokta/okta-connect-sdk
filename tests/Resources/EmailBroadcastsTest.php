<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\EmailBroadcast;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class EmailBroadcastsTest extends TestCase
{
    public function test_list_passes_status_filter_as_query_string(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'bc_1', 'name' => 'Launch', 'status' => 'queued']],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->emails()->broadcasts()->list(['status' => 'queued']);

        $this->assertCount(1, $page);
        $request = $history[0]['request'];
        $this->assertSame('/api/v1/emails/broadcasts', $request->getUri()->getPath());
        $this->assertStringContainsString('status=queued', $request->getUri()->getQuery());
    }

    public function test_get_returns_broadcast_dto(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => ['id' => 'bc_1', 'name' => 'Launch', 'status' => 'sending', 'queued_count' => 100, 'sent_count' => 40],
            ]),
        ], $history);

        $broadcast = $client->emails()->broadcasts()->get('bc_1');

        $this->assertInstanceOf(EmailBroadcast::class, $broadcast);
        $this->assertSame(100, $broadcast->queuedCount);
        $this->assertSame(40, $broadcast->sentCount);
        $this->assertSame('/api/v1/emails/broadcasts/bc_1', $history[0]['request']->getUri()->getPath());
    }

    public function test_create_posts_to_broadcasts_collection(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'bc_2', 'name' => 'Newsletter', 'status' => 'draft']]),
        ], $history);

        $broadcast = $client->emails()->broadcasts()->create([
            'name' => 'Newsletter',
            'from' => 'Acme <news@acme.com>',
            'subject' => 'March news',
            'html' => '<h1>Hi</h1>',
            'audience' => ['tag_slugs' => ['vip']],
        ], idempotencyKey: 'bc-1');

        $this->assertInstanceOf(EmailBroadcast::class, $broadcast);
        $this->assertSame('bc_2', $broadcast->id);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/emails/broadcasts', $request->getUri()->getPath());
        $this->assertSame('bc-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('Newsletter', $body['name']);
        $this->assertSame('Acme <news@acme.com>', $body['from']);
        $this->assertSame(['tag_slugs' => ['vip']], $body['audience']);
    }

    public function test_queue_posts_to_queue_action_path(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'bc_2', 'name' => 'Newsletter', 'status' => 'queued']]),
        ], $history);

        $broadcast = $client->emails()->broadcasts()->queue('bc_2');

        $this->assertSame('queued', $broadcast->status);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/emails/broadcasts/bc_2/queue', $request->getUri()->getPath());
    }
}
