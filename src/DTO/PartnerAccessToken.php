<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * The result of `POST /api/v1/partner/token` — a short-lived bearer for the
 * Partner API, plus the abilities the platform actually granted it.
 *
 * You rarely construct this: PartnerTransport exchanges and refreshes on
 * your behalf. It is public because "what may this token do, and until
 * when" is worth logging at the start of a provisioning run.
 */
final class PartnerAccessToken
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly ?int $expiresIn,
        public readonly ?string $expiresAt,
        public readonly array $abilities = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $abilities = [];

        if (isset($data['abilities']) && is_array($data['abilities'])) {
            foreach ($data['abilities'] as $ability) {
                $abilities[] = (string) $ability;
            }
        }

        $expiresIn = isset($data['expires_in']) && is_numeric($data['expires_in'])
            ? (int) $data['expires_in']
            : null;

        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            // The platform sends 0 for a token that does not expire; that is
            // "no deadline", not "expired a moment ago".
            expiresIn: $expiresIn === 0 ? null : $expiresIn,
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            abilities: $abilities,
        );
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    /**
     * Absolute expiry as a unix timestamp. Prefers the server's `expires_at`
     * over `expires_in` — the former survives a slow response, the latter
     * starts counting from whenever we happened to parse it.
     */
    public function expiresAtTimestamp(): ?int
    {
        if ($this->expiresAt !== null && $this->expiresAt !== '') {
            $parsed = strtotime($this->expiresAt);

            if ($parsed !== false) {
                return $parsed;
            }
        }

        return $this->expiresIn !== null ? time() + $this->expiresIn : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt,
        ], static fn ($v): bool => $v !== null) + ['abilities' => $this->abilities];
    }
}
