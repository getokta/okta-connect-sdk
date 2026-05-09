<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Exceptions;

use Okta\Connect\WhatsApp\Http\Response;
use RuntimeException;
use Throwable;

/**
 * Base class for all SDK errors.
 *
 * Wraps the originating HTTP response (when there was one) so callers can
 * inspect the raw body, headers, and decoded JSON without re-issuing the
 * request.
 */
class WhatsAppException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        protected readonly ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function statusCode(): int
    {
        return $this->code;
    }

    public function response(): ?Response
    {
        return $this->response;
    }

    public function responseBody(): ?string
    {
        return $this->response?->rawBody;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function responseJson(): array
    {
        return $this->response?->json() ?? [];
    }
}
