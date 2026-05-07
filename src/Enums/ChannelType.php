<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Enums;

enum ChannelType: string
{
    case WhatsAppCloud = 'whatsapp_cloud';
    case WhatsAppBaileys = 'whatsapp_baileys';
}
