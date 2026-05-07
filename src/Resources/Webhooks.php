<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources;

/**
 * Webhook subscription management.
 *
 * NOTE (deviation from current tenant routes): As of writing,
 * `app/Modules/Integration/Routes/api.php` does not expose a
 * POST /api/v1/webhooks endpoint — webhook URLs are configured per
 * channel/admin. This client method is included per the brief and
 * targets the documented public-API URL; the server-side route is
 * expected to be added in a follow-up. Calls will surface a 404
 * NotFoundException until then.
 */
final class Webhooks extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function register(array $payload, ?string $idempotencyKey = null): array
    {
        $response = $this->http->post(
            '/api/v1/webhooks',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return $this->unwrap($response->json());
    }
}
