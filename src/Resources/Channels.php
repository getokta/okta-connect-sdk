<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Channel;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

final class Channels extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Channel>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/channels', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Channel => Channel::fromArray($item),
        );
    }

    public function get(string $id): Channel
    {
        $response = $this->http->get('/api/v1/channels/'.rawurlencode($id));

        return Channel::fromArray($this->unwrap($response->json()));
    }
}
