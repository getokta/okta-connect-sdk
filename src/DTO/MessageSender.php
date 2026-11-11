<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Who wrote an outbound message: the member it was sent as.
 */
final class MessageSender
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $externalId,
        public readonly ?string $name,
        public readonly ?string $email,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            externalId: isset($data['external_id']) ? (string) $data['external_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
        );
    }
}
