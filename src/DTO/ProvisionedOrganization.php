<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Response from `POST /api/v1/admin/organizations`.
 *
 * Carries the freshly-created Organization, its owner User, and the
 * plaintext Sanctum token — the caller MUST persist that token
 * encrypted, the platform won't show it again.
 */
final readonly class ProvisionedOrganization
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public string $organizationId,
        public string $organizationSlug,
        public string $organizationName,
        public string $userId,
        public string $userEmail,
        public string $accessToken,
        public array $abilities,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $org */
        $org = is_array($data['organization'] ?? null) ? $data['organization'] : [];
        /** @var array<string, mixed> $user */
        $user = is_array($data['user'] ?? null) ? $data['user'] : [];

        $rawAbilities = is_array($data['abilities'] ?? null) ? $data['abilities'] : [];
        /** @var list<string> $abilities */
        $abilities = array_values(array_filter($rawAbilities, 'is_string'));

        return new self(
            organizationId: (string) ($org['id'] ?? ''),
            organizationSlug: (string) ($org['slug'] ?? ''),
            organizationName: (string) ($org['name'] ?? ''),
            userId: (string) ($user['id'] ?? ''),
            userEmail: (string) ($user['email'] ?? ''),
            accessToken: (string) ($data['access_token'] ?? ''),
            abilities: $abilities,
        );
    }
}
