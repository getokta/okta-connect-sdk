<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\EmailBroadcast;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/emails/broadcasts — bulk email sends.
 *
 * Reached via the parent accessor: `$client->emails()->broadcasts()`.
 * Create a draft, then `queue()` it to materialise its audience and
 * start (or schedule) the fan-out.
 */
final class EmailBroadcasts extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<EmailBroadcast>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/emails/broadcasts', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): EmailBroadcast => EmailBroadcast::fromArray($item),
        );
    }

    public function get(string $ulid): EmailBroadcast
    {
        $response = $this->http->get('/api/v1/emails/broadcasts/'.rawurlencode($ulid));

        return EmailBroadcast::fromArray($this->unwrap($response->json()));
    }

    /**
     * Create a broadcast draft. Body: `name` (required), `from`
     * (required), one of `html` / `text`, optional `subject`,
     * `audience` ({tag_slugs: string[]}) and `scheduled_at`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): EmailBroadcast
    {
        $response = $this->http->post(
            '/api/v1/emails/broadcasts',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return EmailBroadcast::fromArray($this->unwrap($response->json()));
    }

    /**
     * Queue a broadcast for sending (or scheduling).
     */
    public function queue(string $ulid): EmailBroadcast
    {
        $response = $this->http->post('/api/v1/emails/broadcasts/'.rawurlencode($ulid).'/queue', []);

        return EmailBroadcast::fromArray($this->unwrap($response->json()));
    }
}
