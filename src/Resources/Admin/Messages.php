<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources\Admin;

use Okta\Connect\WhatsApp\DTO\TransactionalMessage;
use Okta\Connect\WhatsApp\Resources\Resource;

/**
 * Platform-workspace transactional messaging.
 *
 * These endpoints produce *outbound-only* messages flagged
 * `transactional=true` on the platform's own WhatsApp number — they
 * never fan out to the agent inbox stream. The calling token needs
 * either the `platform.admin` or `platform.inbox` ability.
 *
 *   $client->admin()->messages()->transactional([
 *       'to'   => '+966500000000',
 *       'type' => 'text',
 *       'text' => 'Your order #1234 has shipped.',
 *   ]);
 *
 *   $client->admin()->messages()->otp([
 *       'to'          => '+966500000000',
 *       'code'        => '482915',
 *       'ttl_seconds' => 300,
 *       'purpose'     => 'two_factor',
 *       'locale'      => 'ar',
 *   ]);
 *
 * Both answer `202 Accepted`. `otp()` is additionally throttled per
 * destination phone server-side; a `429` surfaces as the SDK's
 * RateLimitException.
 */
final class Messages extends Resource
{
    /**
     * Send a one-shot transactional message (freeform `text` or a Cloud
     * API `template` envelope, switched by the `type` field).
     *
     * @param  array{to: string, type: string, text?: string, template?: array<string, mixed>, channel_id?: string, metadata?: array<string, mixed>}  $payload
     */
    public function transactional(array $payload, ?string $idempotencyKey = null): TransactionalMessage
    {
        $response = $this->http->post(
            '/api/v1/admin/messages/transactional',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return TransactionalMessage::fromArray($this->unwrap((array) $response->json()));
    }

    /**
     * Send a one-time password over WhatsApp. `purpose` is one of
     * registration|two_factor|password_reset|email_verify|other and
     * `locale` is `ar` or `en` (matching the approved OTP templates).
     *
     * @param  array{to: string, code: string, ttl_seconds: int, purpose: string, locale: string, metadata?: array<string, mixed>}  $payload
     */
    public function otp(array $payload, ?string $idempotencyKey = null): TransactionalMessage
    {
        $response = $this->http->post(
            '/api/v1/admin/messages/otp',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return TransactionalMessage::fromArray($this->unwrap((array) $response->json()));
    }
}
