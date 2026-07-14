<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\PaginatedResult;
use Okta\Connect\WhatsApp\DTO\Ticket;

/**
 * Support tickets — open a ticket, move it across pipeline stages, and read the
 * queue. Requires `read` to list/show, `write` to open/transition.
 */
final class Tickets extends Resource
{
    /**
     * List tickets. Optional filters: `status` (a stage category —
     * open/in_progress/on_hold/resolved/closed), `pipeline_id`, `per_page`.
     *
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Ticket>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get($this->api('/tickets'), $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Ticket => Ticket::fromArray($item),
        );
    }

    public function get(string $id): Ticket
    {
        $response = $this->http->get($this->api('/tickets/'.rawurlencode($id)));

        return Ticket::fromArray($this->unwrap($response->json()));
    }

    /**
     * Open a ticket. Body: `subject` (required), optional `description`,
     * `priority`, `pipeline_id`, `contact_id`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function open(array $payload, ?string $idempotencyKey = null): Ticket
    {
        $response = $this->http->post($this->api('/tickets'), $payload, $this->idempotencyHeader($idempotencyKey));

        return Ticket::fromArray($this->unwrap($response->json()));
    }

    /**
     * Move a ticket to another stage. Body: `stage_id` (required, a stage ULID),
     * optional `note`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function transition(string $id, array $payload): Ticket
    {
        $response = $this->http->post($this->api('/tickets/'.rawurlencode($id).'/transition'), $payload);

        return Ticket::fromArray($this->unwrap($response->json()));
    }
}
