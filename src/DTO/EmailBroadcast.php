<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A bulk email broadcast as returned by `/api/v1/emails/broadcasts`.
 *
 * Counters reflect the fan-out progress at read time. Unknown fields
 * fall through into `extra`.
 */
final class EmailBroadcast
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $subject,
        public readonly ?string $status,
        public readonly ?int $queuedCount,
        public readonly ?int $sentCount,
        public readonly ?int $failedCount,
        public readonly ?string $scheduledAt,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'id', 'name', 'subject', 'status', 'queued_count', 'sent_count',
            'failed_count', 'scheduled_at', 'created_at',
        ];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            queuedCount: isset($data['queued_count']) ? (int) $data['queued_count'] : null,
            sentCount: isset($data['sent_count']) ? (int) $data['sent_count'] : null,
            failedCount: isset($data['failed_count']) ? (int) $data['failed_count'] : null,
            scheduledAt: isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
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
            'name' => $this->name,
            'subject' => $this->subject,
            'status' => $this->status,
            'queued_count' => $this->queuedCount,
            'sent_count' => $this->sentCount,
            'failed_count' => $this->failedCount,
            'scheduled_at' => $this->scheduledAt,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
