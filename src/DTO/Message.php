<?php

declare(strict_types=1);

namespace Okta\WhatsApp\DTO;

use Okta\WhatsApp\Enums\MessageStatus;
use Okta\WhatsApp\Enums\MessageType;

/**
 * Represents a WhatsApp message as returned by the API.
 *
 * Field naming mirrors the JSON response. Optional fields are nullable;
 * unknown fields fall through into `extra` so the SDK doesn't strip data
 * the server may add later.
 */
final class Message
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $conversationId,
        public readonly ?string $channelId,
        public readonly ?string $to,
        public readonly ?string $from,
        public readonly ?MessageType $type,
        public readonly ?MessageStatus $status,
        public readonly ?string $body,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'conversation_id', 'channel_id', 'to', 'from', 'type', 'status', 'body', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            conversationId: isset($data['conversation_id']) ? (string) $data['conversation_id'] : null,
            channelId: isset($data['channel_id']) ? (string) $data['channel_id'] : null,
            to: isset($data['to']) ? (string) $data['to'] : null,
            from: isset($data['from']) ? (string) $data['from'] : null,
            type: isset($data['type']) && is_string($data['type']) ? MessageType::tryFrom($data['type']) : null,
            status: isset($data['status']) && is_string($data['status']) ? MessageStatus::tryFrom($data['status']) : null,
            body: isset($data['body']) ? (string) $data['body'] : null,
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
            'conversation_id' => $this->conversationId,
            'channel_id' => $this->channelId,
            'to' => $this->to,
            'from' => $this->from,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'body' => $this->body,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
