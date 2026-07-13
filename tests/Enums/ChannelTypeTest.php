<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Enums;

use Okta\Connect\WhatsApp\Enums\ChannelType;
use PHPUnit\Framework\TestCase;

final class ChannelTypeTest extends TestCase
{
    /** Values must stay identical to the platform API's `type` field. */
    public function test_values_match_platform_api(): void
    {
        $expected = [
            'cloud_api', 'baileys', 'embed',
            'telegram', 'instagram_dm', 'messenger', 'twitter', 'linkedin', 'tiktok',
            'email',
        ];

        $this->assertSame($expected, array_column(ChannelType::cases(), 'value'));
    }

    public function test_whatsapp_family(): void
    {
        $this->assertTrue(ChannelType::CloudApi->isWhatsApp());
        $this->assertTrue(ChannelType::Baileys->isWhatsApp());
        $this->assertFalse(ChannelType::Telegram->isWhatsApp());
        $this->assertFalse(ChannelType::Email->isWhatsApp());
    }

    public function test_social_family(): void
    {
        foreach ([ChannelType::Telegram, ChannelType::InstagramDm, ChannelType::Messenger, ChannelType::Twitter, ChannelType::LinkedIn, ChannelType::TikTok] as $case) {
            $this->assertTrue($case->isSocial(), $case->value);
        }

        $this->assertFalse(ChannelType::CloudApi->isSocial());
        $this->assertFalse(ChannelType::Email->isSocial());
    }

    /** The regression 1.0 fixes: real API payloads must hydrate. */
    public function test_try_from_resolves_real_api_values(): void
    {
        $this->assertSame(ChannelType::CloudApi, ChannelType::tryFrom('cloud_api'));
        $this->assertSame(ChannelType::Baileys, ChannelType::tryFrom('baileys'));
        $this->assertNull(ChannelType::tryFrom('whatsapp_cloud'));
        $this->assertNull(ChannelType::tryFrom('whatsapp_baileys'));
    }
}
