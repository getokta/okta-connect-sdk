<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Enums;

enum ChannelType: string
{
    case WhatsAppCloud = 'whatsapp_cloud';
    case WhatsAppBaileys = 'whatsapp_baileys';
    case Telegram = 'telegram';
    case InstagramDm = 'instagram_dm';
    case Twitter = 'twitter';
    case LinkedIn = 'linkedin';
    case TikTok = 'tiktok';
    case Email = 'email';
}
