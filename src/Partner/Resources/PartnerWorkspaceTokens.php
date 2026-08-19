<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\DTO\WorkspaceToken;

/**
 * Tenant API tokens for a workspace you manage — the credential your product
 * uses to act on that workspace's own data plane (`/api/v1/*`), without a
 * human copy-pasting a key out of the dashboard.
 *
 * Ability: `tokens.write` (it covers reads here too).
 */
final class PartnerWorkspaceTokens extends PartnerResource
{
    /**
     * @return list<WorkspaceToken>
     */
    public function list(string $workspaceUlid): array
    {
        $response = $this->http->get($this->workspacePath($workspaceUlid, '/tokens'));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $rows = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        $tokens = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $tokens[] = WorkspaceToken::fromArray($row);
                }
            }
        }

        return $tokens;
    }

    /**
     * Mint a token for one member of the workspace.
     *
     * `abilities` is a subset of `read`, `write`, `send`, `webhooks`.
     * `admin` and every `platform.*` grant are refused server-side.
     *
     * The plain token is on the returned DTO and nowhere else — store it
     * now, along with {@see WorkspaceToken::$id}, which is what revokes it
     * on rotation.
     *
     * @param  list<string>  $abilities
     */
    public function create(
        string $workspaceUlid,
        string $name,
        string $userUlid,
        array $abilities = ['read'],
        ?string $expiresAt = null,
    ): WorkspaceToken {
        $body = [
            'name' => $name,
            'user_id' => $userUlid,
            'abilities' => $abilities,
        ];

        if ($expiresAt !== null) {
            $body['expires_at'] = $expiresAt;
        }

        $response = $this->http->post($this->workspacePath($workspaceUlid, '/tokens'), $body);

        return WorkspaceToken::fromArray($this->unwrap($response->json()));
    }

    /**
     * Revoke one token by its numeric id.
     *
     * @param  int|string  $tokenId  {@see WorkspaceToken::$id} — the API
     *                               returns it under `id` and `token_id` alike.
     */
    public function revoke(string $workspaceUlid, int|string $tokenId): bool
    {
        $response = $this->http->delete(
            $this->workspacePath($workspaceUlid, '/tokens/'.rawurlencode((string) $tokenId)),
        );

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return (bool) ($payload['revoked'] ?? true);
    }
}
