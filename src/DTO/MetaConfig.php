<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Response from `GET /api/integrations/meta/config`.
 *
 * Carries the parameters a partner UI needs to boot Meta's JS SDK
 * and trigger the WhatsApp Embedded Signup flow natively (no iframe).
 */
final readonly class MetaConfig
{
    public function __construct(
        public string $appId,
        public string $configId,
        public string $graphVersion,
        public string $signupMode,
        public bool $available,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            appId: (string) ($data['app_id'] ?? ''),
            configId: (string) ($data['config_id'] ?? ''),
            graphVersion: (string) ($data['graph_version'] ?? 'v23.0'),
            signupMode: (string) ($data['signup_mode'] ?? 'standard'),
            available: (bool) ($data['available'] ?? false),
        );
    }
}
