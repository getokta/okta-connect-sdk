<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Outbound-only message returned by the admin transactional/OTP
 * endpoints (`POST /api/v1/admin/messages/transactional` and
 * `POST /api/v1/admin/messages/otp`).
 *
 * Both endpoints answer `202 Accepted` with this slimmer envelope —
 * the message is queued for delivery and never fans out to the agent
 * inbox stream. `id` is the platform's `msg_<ulid>` identifier.
 */
final readonly class TransactionalMessage
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public ?string $id,
        public ?string $status,
        public ?string $to,
        public ?string $channelId,
        public ?string $createdAt,
        public array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'status', 'to', 'channel_id', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            to: isset($data['to']) ? (string) $data['to'] : null,
            channelId: isset($data['channel_id']) ? (string) $data['channel_id'] : null,
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
            'status' => $this->status,
            'to' => $this->to,
            'channel_id' => $this->channelId,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
