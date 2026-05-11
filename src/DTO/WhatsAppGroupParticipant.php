<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

final readonly class WhatsAppGroupParticipant
{
    public function __construct(
        public string $jid,
        public ?string $phone,
        public ?string $displayName,
        public ?string $avatarUrl,
        public string $role,
        public ?string $joinedAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            jid: (string) ($data['jid'] ?? ''),
            phone: is_string($data['phone'] ?? null) ? $data['phone'] : null,
            displayName: is_string($data['display_name'] ?? null) ? $data['display_name'] : null,
            avatarUrl: is_string($data['avatar_url'] ?? null) ? $data['avatar_url'] : null,
            role: (string) ($data['role'] ?? 'member'),
            joinedAt: is_string($data['joined_at'] ?? null) ? $data['joined_at'] : null,
        );
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }
}
