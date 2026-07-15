<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A snapshot of the calling token's grant, returned by
 * GET /api/v1/oauth/introspect (via `Client::connection()`).
 *
 * Use it to check what your app is allowed to do before attempting a call, and
 * to decide whether you need to request more:
 *
 *   $conn = $client->connection();
 *   if (! $conn->can('send')) {
 *       // send the user back through Connect with the fuller ability set —
 *       // the consent screen highlights what's new.
 *   }
 */
final class Connection
{
    /**
     * @param  list<string>          $abilities
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $appName,
        public readonly array $abilities,
        public readonly ?string $organizationId,
        public readonly ?string $organizationName,
        public readonly ?string $expiresAt,
        public readonly ?string $lastUsedAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['app_name', 'abilities', 'organization', 'expires_at', 'last_used_at'];
        $extra = array_diff_key($data, array_flip($known));

        $abilities = [];
        if (isset($data['abilities']) && is_array($data['abilities'])) {
            foreach ($data['abilities'] as $ability) {
                $abilities[] = (string) $ability;
            }
        }

        $org = isset($data['organization']) && is_array($data['organization']) ? $data['organization'] : [];

        return new self(
            appName: isset($data['app_name']) ? (string) $data['app_name'] : '',
            abilities: $abilities,
            organizationId: isset($org['id']) ? (string) $org['id'] : null,
            organizationName: isset($org['name']) ? (string) $org['name'] : null,
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            lastUsedAt: isset($data['last_used_at']) ? (string) $data['last_used_at'] : null,
            extra: $extra,
        );
    }

    /** True when the token holds the given ability. */
    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    /**
     * Abilities in $wanted that this connection does NOT yet hold — the set you
     * would need to add. Empty means the current grant already covers $wanted.
     *
     * @param  list<string>  $wanted
     * @return list<string>
     */
    public function missing(array $wanted): array
    {
        return array_values(array_filter($wanted, fn (string $a): bool => ! $this->can($a)));
    }
}
