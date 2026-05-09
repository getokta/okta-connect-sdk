<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Exceptions;

use Okta\Connect\WhatsApp\Http\Response;
use Throwable;

/**
 * Token exceeded its per-window rate limit (HTTP 429).
 *
 * `retryAfter()` returns the number of seconds the caller should wait
 * before retrying — derived from the `Retry-After` header.
 */
final class RateLimitException extends WhatsAppException
{
    public function __construct(
        string $message,
        int $code,
        ?Response $response,
        private readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $response, $previous);
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
