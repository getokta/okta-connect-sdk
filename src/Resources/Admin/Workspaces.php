<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources\Admin;

use Okta\WhatsApp\DTO\PaginatedResult;
use Okta\WhatsApp\DTO\Workspace;
use Okta\WhatsApp\Resources\Resource;

final class Workspaces extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): Workspace
    {
        $response = $this->http->post(
            '/api/v1/admin/workspaces',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Workspace::fromArray($this->unwrap($response->json()));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Workspace>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/admin/workspaces', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Workspace => Workspace::fromArray($item),
        );
    }

    public function get(string $ulid): Workspace
    {
        $response = $this->http->get('/api/v1/admin/workspaces/'.rawurlencode($ulid));

        return Workspace::fromArray($this->unwrap($response->json()));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $ulid, array $payload, ?string $idempotencyKey = null): Workspace
    {
        $response = $this->http->patch(
            '/api/v1/admin/workspaces/'.rawurlencode($ulid),
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Workspace::fromArray($this->unwrap($response->json()));
    }
}
