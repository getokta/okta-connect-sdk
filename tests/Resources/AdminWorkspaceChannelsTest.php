<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Enums\ChannelType;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class AdminWorkspaceChannelsTest extends TestCase
{
    public function test_create_channel_under_workspace(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'ch_1', 'display_name' => 'Acme Main', 'type' => 'whatsapp_cloud'],
            ]),
        ], $history);

        $channel = $client->admin()->workspaceChannels()->create('01H', [
            'display_name' => 'Acme Main', 'type' => 'whatsapp_cloud',
        ]);

        $this->assertSame(ChannelType::WhatsAppCloud, $channel->type);
        $this->assertSame('/api/v1/admin/workspaces/01H/channels', $history[0]['request']->getUri()->getPath());
    }

    public function test_list_channels(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->admin()->workspaceChannels()->list('01H');

        $this->assertSame('/api/v1/admin/workspaces/01H/channels', $history[0]['request']->getUri()->getPath());
        $this->assertSame('GET', $history[0]['request']->getMethod());
    }
}
