<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

final class WorkspaceToken
{
    /**
     * Represents a workspace-scoped Sanctum token.
     *
     * `plainTextToken` is only present on issuance — subsequent reads from the
     * server return the metadata fields without the secret.
     *
     * @param  list<string>            $abilities
     * @param  array<string, mixed>    $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $workspaceId,
        public readonly ?string $name,
        public readonly array $abilities,
        public readonly ?string $plainTextToken,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'workspace_id', 'name', 'abilities', 'plain_text_token', 'token', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        $abilities = [];
        if (isset($data['abilities']) && is_array($data['abilities'])) {
            foreach ($data['abilities'] as $ability) {
                $abilities[] = (string) $ability;
            }
        }

        // Server may use `plain_text_token` (Sanctum default) or `token`.
        $plain = null;
        if (isset($data['plain_text_token']) && is_string($data['plain_text_token'])) {
            $plain = $data['plain_text_token'];
        } elseif (isset($data['token']) && is_string($data['token'])) {
            $plain = $data['token'];
        }

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            abilities: $abilities,
            plainTextToken: $plain,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: $extra,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = array_filter([
            'id' => $this->id,
            'workspace_id' => $this->workspaceId,
            'name' => $this->name,
            'plain_text_token' => $this->plainTextToken,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null);

        $out['abilities'] = $this->abilities;

        return $out + $this->extra;
    }
}
