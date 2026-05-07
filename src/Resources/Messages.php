<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources;

use Okta\WhatsApp\DTO\Message;
use Okta\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/messages — outbound send and inbound listing.
 *
 * Note on listing: the canonical tenant route is
 * `GET /api/v1/conversations/{id}/messages`. For convenience we
 * accept either the full filter array (which must include
 * `conversation_id`) or call the conversation-scoped path directly
 * via Conversations::messages().
 */
final class Messages extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload, ?string $idempotencyKey = null): Message
    {
        $response = $this->http->post(
            '/api/v1/messages',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Message::fromArray($this->unwrap($response->json()));
    }

    /**
     * List messages. When `conversation_id` is supplied, hits the canonical
     * conversation-scoped route; otherwise falls back to the flat /messages
     * endpoint (which a future tenant route may expose).
     *
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Message>
     */
    public function list(array $filters = []): PaginatedResult
    {
        if (isset($filters['conversation_id']) && is_string($filters['conversation_id'])) {
            $conversationId = $filters['conversation_id'];
            unset($filters['conversation_id']);
            $response = $this->http->get(
                '/api/v1/conversations/'.rawurlencode($conversationId).'/messages',
                $filters,
            );
        } else {
            $response = $this->http->get('/api/v1/messages', $filters);
        }

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Message => Message::fromArray($item),
        );
    }
}
