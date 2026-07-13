<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Enums;

/**
 * Channel platform types, value-identical to the platform API's `type`
 * field so `ChannelType::tryFrom($channel['type'])` always resolves.
 *
 * 1.0 BREAKING: the WhatsApp cases were renamed to match the API —
 * `WhatsAppCloud = 'whatsapp_cloud'` → `CloudApi = 'cloud_api'` and
 * `WhatsAppBaileys = 'whatsapp_baileys'` → `Baileys = 'baileys'`. The old
 * values never matched real API responses, so DTO hydration returned null
 * for WhatsApp channels.
 */
enum ChannelType: string
{
    // WhatsApp flavours
    case CloudApi = 'cloud_api';
    case Baileys = 'baileys';
    case Embed = 'embed';

    // Social platforms
    case Telegram = 'telegram';
    case InstagramDm = 'instagram_dm';
    case Messenger = 'messenger';
    case Twitter = 'twitter';
    case LinkedIn = 'linkedin';
    case TikTok = 'tiktok';

    // Transactional / non-social channels
    case Email = 'email';

    /**
     * True for the WhatsApp family — the same set the API's `type=whatsapp`
     * filter alias expands to.
     */
    public function isWhatsApp(): bool
    {
        return in_array($this, [self::CloudApi, self::Baileys], true);
    }

    /** True for the non-WhatsApp social platforms. */
    public function isSocial(): bool
    {
        return in_array($this, [
            self::Telegram,
            self::InstagramDm,
            self::Messenger,
            self::Twitter,
            self::LinkedIn,
            self::TikTok,
        ], true);
    }
}
