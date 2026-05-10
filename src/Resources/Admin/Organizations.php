<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Admin;

use Okta\Connect\WhatsApp\DTO\ProvisionedOrganization;
use Okta\Connect\WhatsApp\Resources\Resource;

/**
 * Backend-to-backend organization (workspace) provisioning.
 *
 * Hits `POST /api/v1/admin/organizations`, which creates a fresh
 * Organization + owner User on the platform and returns a usable
 * Sanctum token in one call — no operator consent screen.
 *
 * The platform model is named `Organization` internally; the SDK
 * exposes the user-facing term "workspace" via
 * `AdminClient::workspaces()` as well, which is an alias for this
 * resource.
 */
final class Organizations extends Resource
{
    /**
     * @param  array{name: string, owner_email: string, owner_password: string, owner_name: string, abilities?: list<string>}  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): ProvisionedOrganization
    {
        $response = $this->http->post(
            '/api/v1/admin/organizations',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return ProvisionedOrganization::fromArray($response->json());
    }
}
