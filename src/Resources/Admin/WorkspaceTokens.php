<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources\Admin;

use Okta\WhatsApp\DTO\WorkspaceToken;
use Okta\WhatsApp\Resources\Resource;

final class WorkspaceTokens extends Resource
{
    /**
     * Issue a workspace-scoped Sanctum token.
     *
     * The server returns the freshly-minted plaintext token in the
     * response body; it cannot be re-fetched after this call.
     *
     * @param  array<string, mixed>  $payload
     */
    public function issue(string $workspaceId, array $payload, ?string $idempotencyKey = null): WorkspaceToken
    {
        $response = $this->http->post(
            '/api/v1/admin/workspaces/'.rawurlencode($workspaceId).'/tokens',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return WorkspaceToken::fromArray($this->unwrap($response->json()));
    }
}
