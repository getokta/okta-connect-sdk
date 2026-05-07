<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources;

use Okta\WhatsApp\DTO\Conversation;
use Okta\WhatsApp\DTO\Message;
use Okta\WhatsApp\DTO\PaginatedResult;

final class Conversations extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Conversation>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/conversations', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Conversation => Conversation::fromArray($item),
        );
    }

    public function get(string $id): Conversation
    {
        $response = $this->http->get('/api/v1/conversations/'.rawurlencode($id));

        return Conversation::fromArray($this->unwrap($response->json()));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Message>
     */
    public function messages(string $id, array $filters = []): PaginatedResult
    {
        $response = $this->http->get(
            '/api/v1/conversations/'.rawurlencode($id).'/messages',
            $filters,
        );

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Message => Message::fromArray($item),
        );
    }
}
