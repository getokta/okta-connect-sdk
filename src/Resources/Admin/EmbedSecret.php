<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Admin;

use Okta\Connect\WhatsApp\Resources\Resource;
use RuntimeException;

/**
 * Companion endpoint for the `/embed/sso` JWT handshake.
 *
 * The platform's EmbedSsoVerifier reads its HS256 secret from a
 * `PlatformSettings::get('embed.shared_secret')` entry. Partner
 * platforms call `sync()` once at workspace-provision time to pull
 * that secret (idempotent — the platform lazily generates one on
 * first call), then sign every subsequent `/embed/sso` JWT with it
 * via `Okta\Connect\WhatsApp\Sso\TokenMinter`.
 *
 * The endpoint requires `platform.admin` ability — same auth scope
 * as the rest of the admin namespace.
 */
final class EmbedSecret extends Resource
{
    /**
     * Fetch (and lazily provision) the embed shared secret.
     *
     * @return string 64-char hex string
     */
    public function sync(): string
    {
        $response = $this->http->post('/api/v1/admin/embed-secret/sync', []);
        $body = $response->json();

        $secret = isset($body['secret']) && is_string($body['secret']) ? $body['secret'] : '';

        if ($secret === '') {
            throw new RuntimeException('Platform returned an empty embed secret.');
        }

        return $secret;
    }

    /**
     * Provision a labelled per-partner secret bound to a specific JWT
     * `issuer` in one round-trip (`POST /api/v1/admin/embed-secret/provision`).
     *
     * Unlike `sync()` — which always returns the legacy single secret
     * issued under `iss=okta-web` — this registers a fresh secret under
     * the partner's own `iss` identity so it authenticates as itself in
     * Connect's audit trail. Idempotent for a (label, issuer) pair;
     * reusing a label with a different issuer is rejected (422) by the
     * platform and surfaces as the SDK's ValidationException.
     *
     * @return array{label: string, issuer: string, secret: string, created: bool}
     */
    public function provision(string $label, string $issuer): array
    {
        $response = $this->http->post('/api/v1/admin/embed-secret/provision', [
            'label' => $label,
            'issuer' => $issuer,
        ]);

        $body = (array) $response->json();

        $secret = isset($body['secret']) && is_string($body['secret']) ? $body['secret'] : '';

        if ($secret === '') {
            throw new RuntimeException('Platform returned an empty embed secret.');
        }

        return [
            'label' => isset($body['label']) ? (string) $body['label'] : $label,
            'issuer' => isset($body['issuer']) ? (string) $body['issuer'] : $issuer,
            'secret' => $secret,
            'created' => (bool) ($body['created'] ?? false),
        ];
    }
}
