<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A member of a partner-managed workspace.
 *
 * The distinction the Partner API draws, and this DTO keeps: `status` is
 * the person's account status platform-wide, `membershipStatus` is their
 * standing in *this* workspace. A partner governs the second and never the
 * first.
 *
 * `oneTimePassword` appears only on the response that created the account,
 * and only when you asked for `password_auto`. Show it once, then let it go
 * — no read can recover it.
 */
final class WorkspaceUser
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $workspaceId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $role,
        public readonly ?string $status,
        public readonly ?string $membershipStatus,
        public readonly ?string $oneTimePassword,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

        $known = [
            'id', 'workspace_id', 'name', 'email', 'role', 'status',
            'membership_status', 'one_time_password', 'created_at',
        ];

        $string = static fn (string $key): ?string => isset($payload[$key]) && $payload[$key] !== null
            ? (string) $payload[$key]
            : null;

        return new self(
            id: $string('id'),
            workspaceId: $string('workspace_id'),
            name: $string('name'),
            email: $string('email'),
            role: $string('role'),
            status: $string('status'),
            membershipStatus: $string('membership_status'),
            oneTimePassword: $string('one_time_password'),
            createdAt: $string('created_at'),
            extra: array_diff_key($payload, array_flip($known)),
        );
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'workspace_id' => $this->workspaceId,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'membership_status' => $this->membershipStatus,
            'one_time_password' => $this->oneTimePassword,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
