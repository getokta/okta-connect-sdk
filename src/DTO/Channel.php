<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

use Okta\Connect\WhatsApp\Enums\ChannelType;

/**
 * A connected (or connecting) channel.
 *
 * `supportsTemplates` says whether the channel can carry WhatsApp message
 * templates: templates live on a WABA, so only a `cloud_api` channel with a
 * `wabaId` qualifies — a QR-paired number has nowhere to send the
 * definition. Pick the channel from this flag rather than guessing.
 */
final class Channel
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $displayName,
        public readonly ?ChannelType $type,
        public readonly ?string $status,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
        public readonly ?bool $supportsTemplates = null,
        public readonly ?string $wabaId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'display_name', 'type', 'status', 'created_at', 'supports_templates', 'waba_id'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            displayName: isset($data['display_name']) ? (string) $data['display_name'] : null,
            type: isset($data['type']) && is_string($data['type']) ? ChannelType::tryFrom($data['type']) : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: $extra,
            supportsTemplates: isset($data['supports_templates']) ? (bool) $data['supports_templates'] : null,
            wabaId: isset($data['waba_id']) && $data['waba_id'] !== '' ? (string) $data['waba_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'display_name' => $this->displayName,
            'type' => $this->type?->value,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'supports_templates' => $this->supportsTemplates,
            'waba_id' => $this->wabaId,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
