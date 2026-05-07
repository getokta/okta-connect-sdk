<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Exceptions;

/** Token lacks the required ability (HTTP 403). */
final class AuthorizationException extends WhatsAppException
{
}
