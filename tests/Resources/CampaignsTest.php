<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Campaign;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class CampaignsTest extends TestCase
{
    public function test_list_passes_filters_as_query_string_and_returns_campaigns(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    [
                        'id' => 'camp_1',
                        'name' => 'Ramadan Sale',
                        'status' => 'running',
                        'channel_id' => 'ch_1',
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->campaigns()->list(['status' => 'running', 'per_page' => 25]);

        $this->assertCount(1, $page);
        $campaign = $page->items()[0];
        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertSame('camp_1', $campaign->id);
        $this->assertSame('Ramadan Sale', $campaign->name);
        $this->assertSame('running', $campaign->status);
        $this->assertSame('ch_1', $campaign->channelId);

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/campaigns', $request->getUri()->getPath());
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('status=running', $query);
        $this->assertStringContainsString('per_page=25', $query);
    }

    public function test_get_hits_the_campaign_by_ulid(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    'id' => 'camp_1',
                    'name' => 'Ramadan Sale',
                    'status' => 'draft',
                    'channel_id' => 'ch_1',
                    'audience_size' => 100,
                    'sent_count' => 0,
                ],
            ]),
        ], $history);

        $campaign = $client->campaigns()->get('camp_1');

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertSame('camp_1', $campaign->id);
        $this->assertSame(100, $campaign->audienceSize);
        $this->assertSame(0, $campaign->sentCount);

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/campaigns/camp_1', $request->getUri()->getPath());
    }

    public function test_create_posts_name_and_channel_id_with_audience_filter_and_idempotency_key(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => [
                    'id' => 'camp_2',
                    'name' => 'New Product Launch',
                    'type' => 'bulk',
                    'status' => 'draft',
                    'channel_id' => 'ch_1',
                ],
            ]),
        ], $history);

        $campaign = $client->campaigns()->create([
            'name' => 'New Product Launch',
            'channel_id' => 'ch_1',
            'template_id' => 'tpl_1',
            'audience_filter' => [
                'contact_ids' => ['co_1', 'co_2'],
                'tag_slugs' => ['vip'],
            ],
        ], idempotencyKey: 'camp-op-1');

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertSame('camp_2', $campaign->id);
        $this->assertSame('New Product Launch', $campaign->name);
        $this->assertSame('bulk', $campaign->type);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/campaigns', $request->getUri()->getPath());
        $this->assertSame('camp-op-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('New Product Launch', $body['name']);
        $this->assertSame('ch_1', $body['channel_id']);
        $this->assertSame('tpl_1', $body['template_id']);
        $this->assertSame(['co_1', 'co_2'], $body['audience_filter']['contact_ids']);
        $this->assertSame(['vip'], $body['audience_filter']['tag_slugs']);
    }

    public function test_queue_posts_to_the_campaign_queue_endpoint(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    'id' => 'camp_1',
                    'name' => 'Ramadan Sale',
                    'status' => 'queueing',
                    'channel_id' => 'ch_1',
                ],
            ]),
        ], $history);

        $campaign = $client->campaigns()->queue('camp_1');

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertSame('queueing', $campaign->status);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/campaigns/camp_1/queue', $request->getUri()->getPath());
    }
}
