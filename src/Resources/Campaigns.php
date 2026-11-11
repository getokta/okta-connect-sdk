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
     * Required: `name`, `channel_id`, and the message itself: `text` (any
     * channel that is not an official WhatsApp number) or `template_id` with
     * `template_params` — one value per `{{N}}` in the template body, in
     * order; a value may hold contact variables such as `{name}`. The server
     * answers 422 when the template is not approved or the value count is
     * wrong, and refuses to queue a campaign that has no message.
     *
     * Optional: `type` (bulk|drip, default bulk), `audience_filter`
     * (`contact_ids` / `tag_slugs` / `engaged_only`). On a QR-linked number
     * `engaged_only` defaults to true — only people who have written to that
     * number — pass false to override.
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
