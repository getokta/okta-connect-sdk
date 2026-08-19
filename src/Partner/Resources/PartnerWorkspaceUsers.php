<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\DTO\MembershipResult;
use Okta\Connect\WhatsApp\DTO\WorkspaceUser;

/**
 * Membership of a workspace you manage.
 *
 * A partner governs membership, not accounts. You can add someone to your
 * workspace, change their role there, suspend or remove that membership.
 * You cannot rename a user, reset an existing user's password, or see
 * anything about the other workspaces they belong to.
 *
 * Abilities: `users.read` / `users.write`.
 */
final class PartnerWorkspaceUsers extends PartnerResource
{
    /**
     * @return list<WorkspaceUser>
     */
    public function list(string $workspaceUlid): array
    {
        $response = $this->http->get($this->workspacePath($workspaceUlid, '/users'));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $rows = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        $users = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $users[] = WorkspaceUser::fromArray($row);
                }
            }
        }

        return $users;
    }

    /**
     * Add a member.
     *
     * An email Connect already knows is **reused**, never overwritten: the
     * existing account simply gains a membership, and their password is
     * untouched. The returned result says which happened, so you know
     * whether there is a one-time password to show.
     *
     * Roles: `owner`, `admin`, `manager`, `agent`, `member` (default
     * `admin`). Adding someone as `owner` transfers workspace ownership.
     *
     * @param  array<string, mixed>  $attributes  name, email, role, password_auto, locale
     */
    public function add(string $workspaceUlid, array $attributes): MembershipResult
    {
        $response = $this->http->post($this->workspacePath($workspaceUlid, '/users'), $attributes);

        return new MembershipResult(
            user: WorkspaceUser::fromArray($this->unwrap($response->json())),
            // 201 minted a new account, 200 attached one that already existed.
            created: $response->statusCode() === 201,
        );
    }

    /**
     * `role` and/or `status` (`active` | `suspended`). The workspace owner
     * cannot be suspended — hand ownership over first.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $workspaceUlid, string $userUlid, array $attributes): WorkspaceUser
    {
        $response = $this->http->patch(
            $this->workspacePath($workspaceUlid, '/users/'.rawurlencode($userUlid)),
            $attributes,
        );

        return WorkspaceUser::fromArray($this->unwrap($response->json()));
    }

    /**
     * Drop the membership. The user account itself survives — it is not
     * yours to delete. The owner cannot be removed; transfer first.
     */
    public function remove(string $workspaceUlid, string $userUlid): bool
    {
        $response = $this->http->delete(
            $this->workspacePath($workspaceUlid, '/users/'.rawurlencode($userUlid)),
        );

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return (bool) ($payload['deleted'] ?? true);
    }
}
