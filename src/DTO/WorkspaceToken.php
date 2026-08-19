<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A tenant API token minted for a workspace you manage — the credential your
 * product uses to act on that workspace's data plane (`/api/v1/*`).
 *
 * `plainTextToken` is present only on the mint response; Connect stores a
 * hash. `id` is what you need to revoke it later — the API returns the same
 * numeric value under both `id` and `token_id`, because the mint response
 * originally used one name and the revoke route documented the other, and a
 * client that stored the documented name stored null with nothing failing
 * loudly until a rotation could not revoke.
 *
 * `abilities` is a subset of `read`, `write`, `send`, `webhooks`. `admin` is
 * not mintable here by design: a partner wires a workspace up, it does not
 * become its administrator through a key the workspace's own people cannot
 * see.
 */
final class WorkspaceToken
{
    /**
     * @param  list<string>          $abilities
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $workspaceId,
        public readonly ?string $name,
        public readonly array $abilities,
        public readonly ?string $plainTextToken,
        public readonly ?string $expiresAt,
        public readonly ?string $lastUsedAt,
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
            'id', 'token_id', 'workspace_id', 'name', 'abilities', 'plain_text_token',
            'token', 'expires_at', 'last_used_at', 'created_at',
        ];

        $abilities = [];

        if (isset($payload['abilities']) && is_array($payload['abilities'])) {
            foreach ($payload['abilities'] as $ability) {
                $abilities[] = (string) $ability;
            }
        }

        // The platform sends both names for the identifier; either is safe to
        // read, and reading both means neither generation of payload leaves
        // this null.
        $id = $payload['id'] ?? $payload['token_id'] ?? null;

        // Sanctum's own name is `plain_text_token`; the Partner API calls the
        // same value `token`.
        $plain = null;

        foreach (['plain_text_token', 'token'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $payload[$key] !== '') {
                $plain = $payload[$key];
                break;
            }
        }

        $string = static fn (string $key): ?string => isset($payload[$key]) && $payload[$key] !== null
            ? (string) $payload[$key]
            : null;

        return new self(
            id: $id === null ? null : (string) $id,
            workspaceId: $string('workspace_id'),
            name: $string('name'),
            abilities: $abilities,
            plainTextToken: $plain,
            expiresAt: $string('expires_at'),
            lastUsedAt: $string('last_used_at'),
            createdAt: $string('created_at'),
            extra: array_diff_key($payload, array_flip($known)),
        );
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'token_id' => $this->id,
            'workspace_id' => $this->workspaceId,
            'name' => $this->name,
            'plain_text_token' => $this->plainTextToken,
            'expires_at' => $this->expiresAt,
            'last_used_at' => $this->lastUsedAt,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + ['abilities' => $this->abilities] + $this->extra;
    }
}
