<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A member of the workspace.
 *
 * `externalId` is the id YOUR system knows the person by (set when you add
 * them); `id` is Connect's. Either works as the `sender` of a message.
 */
final class Member
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $externalId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $role,
        public readonly ?string $status,
        public readonly ?string $joinedAt,
        public readonly array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'external_id', 'name', 'email', 'role', 'status', 'joined_at'];

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            externalId: isset($data['external_id']) ? (string) $data['external_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            role: isset($data['role']) ? (string) $data['role'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            joinedAt: isset($data['joined_at']) ? (string) $data['joined_at'] : null,
            extra: array_diff_key($data, array_flip($known)),
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The value to pass as `sender`: your own id when the member has one
     * (it survives re-provisioning), otherwise Connect's.
     */
    public function senderReference(): ?string
    {
        return $this->externalId !== null && $this->externalId !== ''
            ? 'ext:'.$this->externalId
            : $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'external_id' => $this->externalId,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joinedAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
