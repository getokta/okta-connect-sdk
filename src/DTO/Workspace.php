<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A workspace (organization) as the Partner API describes it.
 *
 * `externalId` is your own identifier for the account, echoed back. It is
 * also the idempotency key: creating twice with the same one returns this
 * same workspace instead of a duplicate, which is what makes a retried
 * provisioning call safe.
 */
final class Workspace
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>       $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $status,
        public readonly ?string $externalId,
        public readonly ?string $locale,
        public readonly ?string $timezone,
        public readonly ?string $country,
        public readonly ?string $planKey,
        public readonly ?string $trialEndsAt,
        public readonly ?array $metadata,
        public readonly ?string $displayName,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
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
            'id', 'name', 'slug', 'status', 'external_id', 'locale', 'timezone', 'country',
            'plan_key', 'trial_ends_at', 'metadata', 'display_name', 'created_at', 'updated_at',
        ];

        $string = static fn (string $key): ?string => isset($payload[$key]) && $payload[$key] !== null
            ? (string) $payload[$key]
            : null;

        return new self(
            id: $string('id'),
            name: $string('name'),
            slug: $string('slug'),
            status: $string('status'),
            externalId: $string('external_id'),
            locale: $string('locale'),
            timezone: $string('timezone'),
            country: $string('country'),
            planKey: $string('plan_key'),
            trialEndsAt: $string('trial_ends_at'),
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
            displayName: $string('display_name'),
            createdAt: $string('created_at'),
            updatedAt: $string('updated_at'),
            extra: array_diff_key($payload, array_flip($known)),
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'external_id' => $this->externalId,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'country' => $this->country,
            'plan_key' => $this->planKey,
            'trial_ends_at' => $this->trialEndsAt,
            'metadata' => $this->metadata,
            'display_name' => $this->displayName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
