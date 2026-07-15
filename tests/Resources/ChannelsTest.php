<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Enums\ChannelType;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ChannelsTest extends TestCase
{
    public function test_list_returns_channels(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => 'ch_1', 'display_name' => 'Main', 'type' => 'cloud_api'],
                ],
            ]),
        ]);

        $page = $client->channels()->list();

        $this->assertCount(1, $page);
        $this->assertSame(ChannelType::CloudApi, $page->items()[0]->type);
    }

    public function test_list_passes_type_and_status_filters_as_query(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->list(['type' => 'telegram', 'status' => 'connected']);

        $request = $history[0]['request'];
        $this->assertSame('/api/v1/channels', $request->getUri()->getPath());
        $this->assertStringContainsString('type=telegram', $request->getUri()->getQuery());
        $this->assertStringContainsString('status=connected', $request->getUri()->getQuery());
    }

    public function test_list_by_type_merges_type_status_and_extra_filters(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->listByType('tiktok', 'connected', ['per_page' => 5]);

        $query = $history[0]['request']->getUri()->getQuery();
        $this->assertStringContainsString('type=tiktok', $query);
        $this->assertStringContainsString('status=connected', $query);
        $this->assertStringContainsString('per_page=5', $query);
    }

    public function test_list_by_type_omits_status_when_null(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->listByType('email');

        $query = $history[0]['request']->getUri()->getQuery();
        $this->assertStringContainsString('type=email', $query);
        $this->assertStringNotContainsString('status=', $query);
    }

    public function test_whatsapp_uses_the_family_alias(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->whatsapp('connected');

        $query = $history[0]['request']->getUri()->getQuery();
        $this->assertStringContainsString('type=whatsapp', $query);
        $this->assertStringContainsString('status=connected', $query);
    }

    public function test_connected_filters_by_status_and_optional_type(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->connected();
        $client->channels()->connected('instagram_dm');

        $this->assertSame('status=connected', $history[0]['request']->getUri()->getQuery());

        $query = $history[1]['request']->getUri()->getQuery();
        $this->assertStringContainsString('type=instagram_dm', $query);
        $this->assertStringContainsString('status=connected', $query);
    }

    public function test_disconnected_filters_by_status(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->disconnected('linkedin');

        $query = $history[0]['request']->getUri()->getQuery();
        $this->assertStringContainsString('type=linkedin', $query);
        $this->assertStringContainsString('status=disconnected', $query);
    }

    public function test_new_channel_types_parse_into_the_enum(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => 'ch_1', 'display_name' => 'Clips', 'type' => 'tiktok'],
                    ['id' => 'ch_2', 'display_name' => 'Corp', 'type' => 'linkedin'],
                    ['id' => 'ch_3', 'display_name' => 'Mail', 'type' => 'email'],
                ],
            ]),
        ]);

        $page = $client->channels()->list();

        $this->assertSame(ChannelType::TikTok, $page->items()[0]->type);
        $this->assertSame(ChannelType::LinkedIn, $page->items()[1]->type);
        $this->assertSame(ChannelType::Email, $page->items()[2]->type);
    }

    public function test_get_unwraps_data_envelope(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'ch_1', 'display_name' => 'Main']]),
        ]);

        $channel = $client->channels()->get('ch_1');

        $this->assertSame('Main', $channel->displayName);
    }

    public function test_awaiting_scan_filters_by_status(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->awaitingScan();
        $client->channels()->awaitingScan('baileys');

        $this->assertSame('status=awaiting_scan', $history[0]['request']->getUri()->getQuery());

        $query = $history[1]['request']->getUri()->getQuery();
        $this->assertStringContainsString('type=baileys', $query);
        $this->assertStringContainsString('status=awaiting_scan', $query);
    }

    public function test_delete_issues_a_delete_and_returns_true(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['deleted' => true]),
        ], $history);

        $ok = $client->channels()->delete('ch_1');

        $this->assertTrue($ok);
        $request = $history[0]['request'];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/api/v1/channels/ch_1', $request->getUri()->getPath());
    }
}
