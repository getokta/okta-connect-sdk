<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * One per-channel delivery target within a SocialPost's fan-out.
 */
final class SocialPostTarget
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $channelId,
        public readonly ?string $status,
        public readonly ?string $targetRef,
        public readonly ?string $permalink,
        public readonly ?string $providerPostId,
        public readonly ?string $publishedAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['channel_id', 'status', 'target_ref', 'permalink', 'provider_post_id', 'published_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            channelId: isset($data['channel_id']) ? (string) $data['channel_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            targetRef: isset($data['target_ref']) ? (string) $data['target_ref'] : null,
            permalink: isset($data['permalink']) ? (string) $data['permalink'] : null,
            providerPostId: isset($data['provider_post_id']) ? (string) $data['provider_post_id'] : null,
            publishedAt: isset($data['published_at']) ? (string) $data['published_at'] : null,
            extra: $extra,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'channel_id' => $this->channelId,
            'status' => $this->status,
            'target_ref' => $this->targetRef,
            'permalink' => $this->permalink,
            'provider_post_id' => $this->providerPostId,
            'published_at' => $this->publishedAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
