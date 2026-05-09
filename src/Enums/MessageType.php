<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Document = 'document';
    case Audio = 'audio';
    case Template = 'template';
    case Location = 'location';
    case Contacts = 'contacts';
    case Interactive = 'interactive';
}
