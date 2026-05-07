<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Exceptions;

/** Token missing, invalid, or expired (HTTP 401). */
final class AuthenticationException extends WhatsAppException
{
}
