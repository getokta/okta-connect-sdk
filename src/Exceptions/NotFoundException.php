<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Exceptions;

/** Resource doesn't exist or is not visible to the caller (HTTP 404). */
final class NotFoundException extends WhatsAppException
{
}
