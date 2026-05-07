<?php

declare(strict_types=1);

namespace Okta\WhatsApp\DTO;

use Okta\WhatsApp\Enums\ChannelType;

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
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'display_name', 'type', 'status', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            displayName: isset($data['display_name']) ? (string) $data['display_name'] : null,
            type: isset($data['type']) && is_string($data['type']) ? ChannelType::tryFrom($data['type']) : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: $extra,
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
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
