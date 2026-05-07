<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Exceptions;

use Okta\WhatsApp\Http\Response;
use Throwable;

/**
 * Request body failed validation (HTTP 422).
 *
 * Exposes the per-field error map exactly as Laravel returns it under the
 * top-level `errors` key.
 */
final class ValidationException extends WhatsAppException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        int $code,
        ?Response $response,
        private readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $response, $previous);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
