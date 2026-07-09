<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Represents a social publishing post (and its per-channel fan-out) as
 * returned by the API. `targets` is parsed into a list of
 * {@see SocialPostTarget} value objects rather than left as raw arrays.
 */
final class SocialPost
{
    /**
     * @param  array<string, mixed>  $media
     * @param  array<string, mixed>|null  $options
     * @param  list<SocialPostTarget>  $targets
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $body,
        public readonly array $media,
        public readonly ?array $options,
        public readonly ?string $scheduledAt,
        public readonly ?string $publishedAt,
        public readonly ?int $targetCount,
        public readonly ?int $publishedCount,
        public readonly ?int $failedCount,
        public readonly ?string $createdAt,
        public readonly array $targets = [],
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'id', 'status', 'body', 'media', 'options', 'scheduled_at', 'published_at',
            'target_count', 'published_count', 'failed_count', 'created_at', 'targets',
        ];
        $extra = array_diff_key($data, array_flip($known));

        $targets = [];
        if (isset($data['targets']) && is_array($data['targets'])) {
            foreach ($data['targets'] as $target) {
                if (is_array($target)) {
                    $targets[] = SocialPostTarget::fromArray($target);
                }
            }
        }

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            body: isset($data['body']) ? (string) $data['body'] : null,
            media: isset($data['media']) && is_array($data['media']) ? $data['media'] : [],
            options: isset($data['options']) && is_array($data['options']) ? $data['options'] : null,
            scheduledAt: isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
            publishedAt: isset($data['published_at']) ? (string) $data['published_at'] : null,
            targetCount: isset($data['target_count']) ? (int) $data['target_count'] : null,
            publishedCount: isset($data['published_count']) ? (int) $data['published_count'] : null,
            failedCount: isset($data['failed_count']) ? (int) $data['failed_count'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            targets: $targets,
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
            'body' => $this->body,
            'media' => $this->media,
            'options' => $this->options,
            'scheduled_at' => $this->scheduledAt,
            'published_at' => $this->publishedAt,
            'target_count' => $this->targetCount,
            'published_count' => $this->publishedCount,
            'failed_count' => $this->failedCount,
            'created_at' => $this->createdAt,
            'targets' => array_map(
                static fn (SocialPostTarget $target): array => $target->toArray(),
                $this->targets,
            ),
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
