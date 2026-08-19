<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\DTO\SsoLink;

/**
 * One-time sign-in links: send an existing member of your workspace
 * straight into the dashboard, already signed in.
 *
 * Note what this is not. It does not create accounts and it does not grant
 * roles — the user must already be an active member, and membership is
 * re-checked at redemption, not just at issue. Remove someone in the
 * meantime and the link they are holding is dead.
 *
 * Ability: `sso.issue`.
 */
final class PartnerSso extends PartnerResource
{
    /**
     * @param  string|null  $redirect  Must start with `/app/`; anything else
     *                                 falls back to `/app`.
     */
    public function issue(string $workspaceUlid, string $userUlid, ?string $redirect = null): SsoLink
    {
        $body = ['user_id' => $userUlid];

        if ($redirect !== null) {
            $body['redirect'] = $redirect;
        }

        $response = $this->http->post($this->workspacePath($workspaceUlid, '/sso'), $body);

        return SsoLink::fromArray($response->json());
    }
}
