<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Thin wrapper around a PSR-7 response.
 *
 * Pre-decodes the JSON body once so resources can read it as an array
 * without each one re-parsing the stream.
 */
final class Response
{
    /** @var array<string, mixed>|list<mixed>|null */
    private array|null $decoded = null;

    public function __construct(
        public readonly ResponseInterface $psrResponse,
        public readonly string $rawBody,
    ) {
    }

    public function statusCode(): int
    {
        return $this->psrResponse->getStatusCode();
    }

    public function header(string $name): ?string
    {
        $values = $this->psrResponse->getHeader($name);

        return $values === [] ? null : $values[0];
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function json(): array
    {
        if ($this->decoded !== null) {
            return $this->decoded;
        }

        if ($this->rawBody === '') {
            return $this->decoded = [];
        }

        $decoded = json_decode($this->rawBody, true);

        if (! is_array($decoded)) {
            return $this->decoded = [];
        }

        return $this->decoded = $decoded;
    }
}
