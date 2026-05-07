<?php

declare(strict_types=1);

namespace Okta\WhatsApp\DTO;

final class Conversation
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $channelId,
        public readonly ?string $contactId,
        public readonly ?string $status,
        public readonly ?string $lastMessageAt,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'channel_id', 'contact_id', 'status', 'last_message_at', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            channelId: isset($data['channel_id']) ? (string) $data['channel_id'] : null,
            contactId: isset($data['contact_id']) ? (string) $data['contact_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            lastMessageAt: isset($data['last_message_at']) ? (string) $data['last_message_at'] : null,
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
            'channel_id' => $this->channelId,
            'contact_id' => $this->contactId,
            'status' => $this->status,
            'last_message_at' => $this->lastMessageAt,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
