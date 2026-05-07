<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Tests\Resources;

use Okta\WhatsApp\Enums\ChannelType;
use Okta\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ChannelsTest extends TestCase
{
    public function test_list_returns_channels(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => 'ch_1', 'display_name' => 'Main', 'type' => 'whatsapp_cloud'],
                ],
            ]),
        ]);

        $page = $client->channels()->list();

        $this->assertCount(1, $page);
        $this->assertSame(ChannelType::WhatsAppCloud, $page->items()[0]->type);
    }

    public function test_get_unwraps_data_envelope(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'ch_1', 'display_name' => 'Main']]),
        ]);

        $channel = $client->channels()->get('ch_1');

        $this->assertSame('Main', $channel->displayName);
    }
}
