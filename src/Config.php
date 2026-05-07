<?php

declare(strict_types=1);

namespace Okta\WhatsApp;

use Psr\Http\Client\ClientInterface;

/**
 * Immutable client configuration.
 *
 * Holds the credentials and transport tunables passed in at Client
 * construction. Once built it is never mutated — resources receive a
 * reference and reuse it across calls.
 */
final class Config
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $token,
        public readonly int $timeout = 30,
        public readonly int $retries = 2,
        public readonly ?ClientInterface $httpClient = null,
        public readonly string $userAgent = 'okta-whatsapp-sdk-php/0.1',
    ) {
    }

    /**
     * Return the trimmed base URL (no trailing slash) for path concatenation.
     */
    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }
}
