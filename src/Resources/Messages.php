<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Message;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

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
     * Send a raw payload to `POST /api/v1/messages`.
     *
     * The server expects a FLAT shape: `channel_id` + `wa_id` (E.164 without
     * `+`) or `conversation_id`, a `type` (text|image|document|audio|video),
     * a flat `body` string, and `media_url` for media types. Prefer the typed
     * helpers below (sendText / sendMedia / reply) so the shape is always
     * correct.
     *
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
     * Send a plain-text message to a destination number, auto-creating the
     * contact + conversation if needed.
     *
     * @param  string  $channelId  The channel ULID to send from.
     * @param  string  $waId       Destination number in E.164 without a leading `+` (e.g. 966500000000).
     */
    public function sendText(string $channelId, string $waId, string $body, ?string $idempotencyKey = null): Message
    {
        return $this->send([
            'channel_id' => $channelId,
            'wa_id' => $waId,
            'type' => 'text',
            'body' => $body,
        ], $idempotencyKey);
    }

    /**
     * Send a media message (image | document | audio | video) by public HTTPS
     * URL, with an optional caption.
     *
     * @param  string  $type  One of: image, document, audio, video.
     */
    public function sendMedia(
        string $channelId,
        string $waId,
        string $type,
        string $mediaUrl,
        string $caption = '',
        ?string $idempotencyKey = null,
    ): Message {
        return $this->send([
            'channel_id' => $channelId,
            'wa_id' => $waId,
            'type' => $type,
            'body' => $caption,
            'media_url' => $mediaUrl,
        ], $idempotencyKey);
    }

    /**
     * Reply into an existing conversation by its ULID.
     */
    public function reply(string $conversationId, string $body, ?string $idempotencyKey = null): Message
    {
        return $this->send([
            'conversation_id' => $conversationId,
            'type' => 'text',
            'body' => $body,
        ], $idempotencyKey);
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
