<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Campaign;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/campaigns — broadcast messaging campaigns.
 *
 * Create a draft campaign against a channel (+ optional template and
 * audience filter), then queue it to materialise its audience and start
 * sending.
 */
final class Campaigns extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Campaign>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/campaigns', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Campaign => Campaign::fromArray($item),
        );
    }

    public function get(string $ulid): Campaign
    {
        $response = $this->http->get('/api/v1/campaigns/'.rawurlencode($ulid));

        return Campaign::fromArray($this->unwrap($response->json()));
    }

    /**
     * Create a draft campaign.
     *
     * Required: `name`, `channel_id`. Optional: `template_id`, `type`
     * (bulk|drip, default bulk), `audience_filter` (`contact_ids` /
     * `tag_slugs`).
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): Campaign
    {
        $response = $this->http->post(
            '/api/v1/campaigns',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Campaign::fromArray($this->unwrap($response->json()));
    }

    /**
     * Queue a draft campaign: materialises its audience and starts sending.
     */
    public function queue(string $ulid): Campaign
    {
        $response = $this->http->post('/api/v1/campaigns/'.rawurlencode($ulid).'/queue');

        return Campaign::fromArray($this->unwrap($response->json()));
    }
}
