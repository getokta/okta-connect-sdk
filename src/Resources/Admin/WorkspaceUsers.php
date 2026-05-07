<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources\Admin;

use Okta\WhatsApp\DTO\PaginatedResult;
use Okta\WhatsApp\DTO\WorkspaceUser;
use Okta\WhatsApp\Resources\Resource;

final class WorkspaceUsers extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $workspaceId, array $payload, ?string $idempotencyKey = null): WorkspaceUser
    {
        $response = $this->http->post(
            '/api/v1/admin/workspaces/'.rawurlencode($workspaceId).'/users',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return WorkspaceUser::fromArray($this->unwrap($response->json()));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<WorkspaceUser>
     */
    public function list(string $workspaceId, array $filters = []): PaginatedResult
    {
        $response = $this->http->get(
            '/api/v1/admin/workspaces/'.rawurlencode($workspaceId).'/users',
            $filters,
        );

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): WorkspaceUser => WorkspaceUser::fromArray($item),
        );
    }
}
