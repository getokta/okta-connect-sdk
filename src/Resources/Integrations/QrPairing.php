<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Integrations;

use Okta\Connect\WhatsApp\DTO\QrSession;
use Okta\Connect\WhatsApp\Resources\Resource;

/**
 * QR pairing flow — companion to Meta Embedded Signup for businesses
 * that don't run on WhatsApp Cloud API.
 *
 * Usage from a partner UI:
 *
 *   $session = $client->qr()->start('Sales line');
 *   // poll every few seconds:
 *   $update = $client->qr()->status($session->id);
 *   if ($update->isConnected()) { ... }
 *
 * The platform handles the heavy lifting: it creates the Channel row
 * on the caller's org, boots the pairing service session asynchronously
 * via ConnectChannelJob, and exposes the live QR string + TTL through
 * the status endpoint. The partner just polls until terminal.
 */
final class QrPairing extends Resource
{
    public function start(string $displayName, ?string $idempotencyKey = null): QrSession
    {
        $response = $this->http->post(
            '/api/integrations/qr/sessions',
            ['display_name' => $displayName],
            $this->idempotencyHeader($idempotencyKey),
        );

        return QrSession::fromArray($response->json());
    }

    public function status(string $ulid): QrSession
    {
        $response = $this->http->get('/api/integrations/qr/sessions/'.rawurlencode($ulid));

        return QrSession::fromArray($response->json());
    }
}
