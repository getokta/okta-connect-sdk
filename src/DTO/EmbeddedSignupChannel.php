<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * One WhatsApp Cloud-API phone number successfully attached to a
 * workspace by `POST /api/integrations/meta/embedded-signup`.
 */
final readonly class EmbeddedSignupChannel
{
    public function __construct(
        public string $id,
        public string $phoneNumber,
        public string $phoneNumberId,
        public string $verifiedName,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            phoneNumber: (string) ($data['phone_number'] ?? ''),
            phoneNumberId: (string) ($data['phone_number_id'] ?? ''),
            verifiedName: (string) ($data['verified_name'] ?? ''),
        );
    }
}
