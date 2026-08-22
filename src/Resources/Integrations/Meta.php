<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Integrations;

use Okta\Connect\WhatsApp\DTO\EmbeddedSignupChannel;
use Okta\Connect\WhatsApp\DTO\MetaConfig;
use Okta\Connect\WhatsApp\Resources\Resource;

/**
 * WhatsApp Embedded Signup (Meta) endpoints.
 *
 * The flow runs ENTIRELY in the partner browser — no iframe, no
 * platform UI. It looks like this:
 *
 *   1. Partner UI calls `$client->meta()->config()` to learn which
 *      Meta App + signup config the platform is wired to.
 *   2. Partner UI loads `https://connect.facebook.net/en_US/sdk.js`,
 *      initialises `FB` with `app_id` + `graph_version`, and renders
 *      a "Connect WhatsApp" button.
 *   3. On click: `FB.login(callback, { config_id, response_type:'code', ... })`.
 *      Meta opens a popup, the operator authorises, FB posts a
 *      `WA_EMBEDDED_SIGNUP` message back to the parent with the
 *      `waba_id` + `phone_number_id`. The login callback yields a
 *      one-shot `code`.
 *   4. Partner UI calls `$client->meta()->completeEmbeddedSignup($code, $wabaId)`
 *      and the platform exchanges the code for a system-user token,
 *      registers the phone number, and creates a Channel row in the
 *      workspace's organization. Missed the postMessage? Call it with
 *      an empty `$wabaId` — the platform reads the id off the token.
 *
 * Auth: tenant-scope Sanctum token (workspace's `access_token`).
 */
final class Meta extends Resource
{
    public function config(): MetaConfig
    {
        $response = $this->http->get('/api/integrations/meta/config');

        return MetaConfig::fromArray($response->json());
    }

    /**
     * Finalise an Embedded Signup session.
     *
     * `$wabaId` comes from Meta's `WA_EMBEDDED_SIGNUP` postMessage — which is
     * delivered to the browser once, with no second chance: a blocked frame,
     * a restored tab, or a completion event name Meta added later loses it
     * permanently. Since platform 2026-08 you may pass an EMPTY string in
     * that case: the platform derives the WABA id from the exchanged token
     * itself (`debug_token` granular scopes) instead of failing a signup the
     * operator already completed. Pass the id when you have it — that skips
     * the extra lookup.
     *
     * @return list<EmbeddedSignupChannel>
     */
    public function completeEmbeddedSignup(string $code, string $wabaId = '', ?string $idempotencyKey = null): array
    {
        $response = $this->http->post(
            '/api/integrations/meta/embedded-signup',
            ['code' => $code, 'waba_id' => $wabaId],
            $this->idempotencyHeader($idempotencyKey),
        );

        $body = $response->json();
        $channels = is_array($body['channels'] ?? null) ? $body['channels'] : [];

        return array_values(array_map(
            static fn (array $c): EmbeddedSignupChannel => EmbeddedSignupChannel::fromArray($c),
            $channels,
        ));
    }
}
