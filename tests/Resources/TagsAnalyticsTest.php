<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class TagsAnalyticsTest extends TestCase
{
    public function test_tags_list_returns_tags(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'tg_1', 'name' => 'VIP', 'slug' => 'vip', 'scope' => 'contact']],
                'meta' => ['current_page' => 1, 'per_page' => 25, 'total' => 1],
            ]),
        ], $history);

        $result = $client->tags()->list(['scope' => 'contact']);

        $this->assertSame('vip', $result->items()[0]->slug);
        $this->assertSame('/api/v1/tags', $history[0]['request']->getUri()->getPath());
    }

    public function test_tags_apply_to_contact_posts_slugs_and_returns_contact(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'ct_1', 'name' => 'Sara']]),
        ], $history);

        $contact = $client->tags()->applyToContact('ct_1', ['vip', 'lead']);

        $this->assertSame('ct_1', $contact->id);
        $req = $history[0]['request'];
        $this->assertSame('/api/v1/contacts/ct_1/tags', $req->getUri()->getPath());
        $body = json_decode((string) $req->getBody(), true);
        $this->assertSame(['vip', 'lead'], $body['tags']);
    }

    public function test_analytics_metrics_returns_typed_totals(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => [
                'from' => '2026-06-01', 'to' => '2026-06-30', 'platform' => 'whatsapp',
                'metrics' => ['messages.inbound' => 120, 'messages.outbound' => 98],
            ]]),
        ], $history);

        $metrics = $client->analytics()->metrics(['from' => '2026-06-01', 'platform' => 'whatsapp']);

        $this->assertSame('whatsapp', $metrics->platform);
        $this->assertSame(120, $metrics->metric('messages.inbound'));
        $this->assertSame(0, $metrics->metric('missing.key'));
        $this->assertSame('/api/v1/analytics/metrics', $history[0]['request']->getUri()->getPath());
    }
}
