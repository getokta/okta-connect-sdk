<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * `GET /api/v1/partner/me` — who the token belongs to and what it may do.
 *
 * Worth calling on boot: it is the cheapest way to prove a key is live and
 * carries the abilities a provisioning sequence is about to need, before
 * that sequence half-completes and leaves a workspace without its owner.
 */
final class PartnerIdentity
{
    /**
     * @param  list<string>          $abilities
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $status,
        public readonly ?string $tokenName,
        public readonly ?string $tokenKind,
        public readonly array $abilities,
        public readonly ?string $tokenExpiresAt,
        public readonly ?int $workspacesCount,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

        $partner = isset($payload['partner']) && is_array($payload['partner']) ? $payload['partner'] : [];
        $token = isset($payload['token']) && is_array($payload['token']) ? $payload['token'] : [];

        $abilities = [];

        if (isset($token['abilities']) && is_array($token['abilities'])) {
            foreach ($token['abilities'] as $ability) {
                $abilities[] = (string) $ability;
            }
        }

        return new self(
            id: isset($partner['id']) ? (string) $partner['id'] : null,
            name: isset($partner['name']) ? (string) $partner['name'] : null,
            slug: isset($partner['slug']) ? (string) $partner['slug'] : null,
            status: isset($partner['status']) ? (string) $partner['status'] : null,
            tokenName: isset($token['name']) ? (string) $token['name'] : null,
            tokenKind: isset($token['kind']) ? (string) $token['kind'] : null,
            abilities: $abilities,
            tokenExpiresAt: isset($token['expires_at']) ? (string) $token['expires_at'] : null,
            workspacesCount: isset($payload['workspaces_count']) && is_numeric($payload['workspaces_count'])
                ? (int) $payload['workspaces_count']
                : null,
            extra: array_diff_key($payload, array_flip(['partner', 'token', 'workspaces_count'])),
        );
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    /**
     * The embed issuer this partner must sign iframe tokens with. Getting
     * this wrong (signing as `okta-web`) is refused server-side, silently
     * from the browser's point of view — so derive it, never type it.
     */
    public function embedIssuer(): ?string
    {
        return $this->id === null ? null : 'partner:'.$this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'partner' => array_filter([
                'id' => $this->id,
                'name' => $this->name,
                'slug' => $this->slug,
                'status' => $this->status,
            ], static fn ($v): bool => $v !== null),
            'token' => array_filter([
                'name' => $this->tokenName,
                'kind' => $this->tokenKind,
                'expires_at' => $this->tokenExpiresAt,
            ], static fn ($v): bool => $v !== null) + ['abilities' => $this->abilities],
            'workspaces_count' => $this->workspacesCount,
        ] + $this->extra;
    }
}
