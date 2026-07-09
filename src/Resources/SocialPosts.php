<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\PaginatedResult;
use Okta\Connect\WhatsApp\DTO\SocialPost;

/**
 * /api/v1/social-posts — compose a post and fan it out to one or more
 * social channels (Telegram, X, Instagram, …). Schedule for later or
 * leave `scheduled_at` unset to save as a draft.
 */
final class SocialPosts extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<SocialPost>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/social-posts', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): SocialPost => SocialPost::fromArray($item),
        );
    }

    public function get(string $ulid): SocialPost
    {
        $response = $this->http->get('/api/v1/social-posts/'.rawurlencode($ulid));

        return SocialPost::fromArray($this->unwrap($response->json()));
    }

    /**
     * Create a social post from a raw payload. Prefer the typed helpers
     * below (schedule / draft) so the shape is always correct.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): SocialPost
    {
        $response = $this->http->post(
            '/api/v1/social-posts',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return SocialPost::fromArray($this->unwrap($response->json()));
    }

    /**
     * Compose a post targeting one or more channels. Pass `$scheduledAt`
     * (ISO-8601) to schedule delivery; omit it (or pass null) to save the
     * post as a draft.
     *
     * @param  list<string>  $channelIds
     * @param  list<array<string, mixed>>  $media
     */
    public function schedule(
        string $text,
        array $channelIds,
        ?string $scheduledAt = null,
        array $media = [],
        ?string $idempotencyKey = null,
    ): SocialPost {
        $payload = [
            'text' => $text,
            'channel_ids' => array_values($channelIds),
        ];

        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt;
        }

        if ($media !== []) {
            $payload['media'] = $media;
        }

        return $this->create($payload, $idempotencyKey);
    }

    /**
     * Save a post as a draft (no `scheduled_at`) targeting one or more
     * channels. Shorthand for `schedule($text, $channelIds, null, ...)`.
     *
     * @param  list<string>  $channelIds
     * @param  list<array<string, mixed>>  $media
     */
    public function draft(
        string $text,
        array $channelIds,
        array $media = [],
        ?string $idempotencyKey = null,
    ): SocialPost {
        return $this->schedule($text, $channelIds, null, $media, $idempotencyKey);
    }
}
