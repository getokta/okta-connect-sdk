<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Admin;

use Okta\Connect\WhatsApp\DTO\Channel;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;
use Okta\Connect\WhatsApp\Resources\Resource;

final class WorkspaceChannels extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $workspaceId, array $payload, ?string $idempotencyKey = null): Channel
    {
        $response = $this->http->post(
            '/api/v1/admin/workspaces/'.rawurlencode($workspaceId).'/channels',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Channel::fromArray($this->unwrap($response->json()));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Channel>
     */
    public function list(string $workspaceId, array $filters = []): PaginatedResult
    {
        $response = $this->http->get(
            '/api/v1/admin/workspaces/'.rawurlencode($workspaceId).'/channels',
            $filters,
        );

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Channel => Channel::fromArray($item),
        );
    }
}
