<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Integrations;

use Okta\Connect\WhatsApp\DTO\QrSession;
use Okta\Connect\WhatsApp\Exceptions\ServerException;
use Okta\Connect\WhatsApp\Exceptions\ValidationException;
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
 * The gateway session is started **inside** the create request, so a
 * successful `start()` means the gateway accepted the boot — not that a
 * code exists yet. A gateway that refuses raises instead of handing back a
 * row that would sit at `pending` forever.
 *
 * Poll every 2–3 seconds and branch on {@see QrSession::$error}, not on
 * elapsed time: `null` means keep waiting, anything else means the loop is
 * waiting for something that is not coming. A session whose row never left
 * `pending` is re-booted by the poll itself, so a channel stranded by an
 * earlier failure heals without a new session.
 */
final class QrPairing extends Resource
{
    /**
     * Create a pairing session.
     *
     * @throws ValidationException `422
     *                             channel_type_unavailable` — the operator has not enabled the
     *                             `baileys` channel type for this workspace. It is an
     *                             availability switch, not a billing one: no plan or paid
     *                             subscription is involved.
     * @throws ServerException `502
     *                         gateway_unavailable` — the pairing gateway refused the boot.
     */
    public function start(string $displayName, ?string $idempotencyKey = null): QrSession
    {
        $response = $this->http->post(
            '/api/integrations/qr/sessions',
            ['display_name' => $displayName],
            $this->idempotencyHeader($idempotencyKey),
        );

        return QrSession::fromArray($response->json());
    }

    /** Poll a session. Stop when {@see QrSession::isTerminal()} or on an error. */
    public function status(string $ulid): QrSession
    {
        $response = $this->http->get('/api/integrations/qr/sessions/'.rawurlencode($ulid));

        return QrSession::fromArray($response->json());
    }
}
