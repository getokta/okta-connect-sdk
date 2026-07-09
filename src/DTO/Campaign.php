<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Represents a broadcast messaging campaign as returned by the API.
 *
 * Field naming mirrors the JSON response. Optional fields are nullable;
 * unknown fields fall through into `extra` so the SDK doesn't strip data
 * the server may add later.
 */
final class Campaign
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $type,
        public readonly ?string $status,
        public readonly ?string $channelId,
        public readonly ?string $templateId,
        public readonly ?int $audienceSize,
        public readonly ?int $queuedCount,
        public readonly ?int $sentCount,
        public readonly ?int $deliveredCount,
        public readonly ?int $readCount,
        public readonly ?int $failedCount,
        public readonly ?string $scheduledAt,
        public readonly ?string $startedAt,
        public readonly ?string $finishedAt,
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
            'id', 'name', 'type', 'status', 'channel_id', 'template_id',
            'audience_size', 'queued_count', 'sent_count', 'delivered_count',
            'read_count', 'failed_count', 'scheduled_at', 'started_at',
            'finished_at', 'created_at',
        ];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            type: isset($data['type']) ? (string) $data['type'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            channelId: isset($data['channel_id']) ? (string) $data['channel_id'] : null,
            templateId: isset($data['template_id']) ? (string) $data['template_id'] : null,
            audienceSize: isset($data['audience_size']) ? (int) $data['audience_size'] : null,
            queuedCount: isset($data['queued_count']) ? (int) $data['queued_count'] : null,
            sentCount: isset($data['sent_count']) ? (int) $data['sent_count'] : null,
            deliveredCount: isset($data['delivered_count']) ? (int) $data['delivered_count'] : null,
            readCount: isset($data['read_count']) ? (int) $data['read_count'] : null,
            failedCount: isset($data['failed_count']) ? (int) $data['failed_count'] : null,
            scheduledAt: isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
            startedAt: isset($data['started_at']) ? (string) $data['started_at'] : null,
            finishedAt: isset($data['finished_at']) ? (string) $data['finished_at'] : null,
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
            'type' => $this->type,
            'status' => $this->status,
            'channel_id' => $this->channelId,
            'template_id' => $this->templateId,
            'audience_size' => $this->audienceSize,
            'queued_count' => $this->queuedCount,
            'sent_count' => $this->sentCount,
            'delivered_count' => $this->deliveredCount,
            'read_count' => $this->readCount,
            'failed_count' => $this->failedCount,
            'scheduled_at' => $this->scheduledAt,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
