<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\PaginatedResult;
use Okta\Connect\WhatsApp\DTO\Webhook;
use Okta\Connect\WhatsApp\DTO\WebhookNotification;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;

/**
 * Webhook subscription management — register outbound webhooks over the API
 * instead of adding them by hand in the dashboard.
 *
 * Deliveries POST to your URL with an `X-Okta-Signature: sha256=<hmac>` header
 * (HMAC-SHA256 of the raw request body using the subscription secret), plus
 * `X-Okta-Event` and `X-Okta-Delivery`. Verify inbound requests with
 * Webhooks::verifySignature($rawBody, $header, $secret).
 */
final class Webhooks extends Resource
{
    /**
     * Create a subscription. The response `secret` is returned EXACTLY ONCE —
     * read `$webhook->secret` now and store it; it can never be fetched again.
     *
     * Body: `name` (required), `url` (required), `events` (required list, use
     * ['*'] for all), optional `max_attempts` (1–20).
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): Webhook
    {
        $response = $this->http->post(
            '/api/v1/webhooks',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Webhook::fromArray($this->unwrap($response->json()));
    }

    /**
     * List the organization's webhook subscriptions. The secret is never
     * included here.
     *
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Webhook>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/webhooks', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Webhook => Webhook::fromArray($item),
        );
    }

    /**
     * Delete a subscription by its ULID. Returns true when removed.
     */
    public function delete(string $id): bool
    {
        $response = $this->http->delete('/api/v1/webhooks/'.rawurlencode($id));

        return ($response->json()['deleted'] ?? false) === true;
    }

    /**
     * @deprecated Use create(), which returns a typed Webhook DTO.
     *
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

    /**
     * Constant-time verification of an inbound webhook's `X-Okta-Signature`
     * header against the RAW request body and the subscription secret. Always
     * verify before trusting a delivery — anyone can POST to your endpoint.
     */
    public static function verifySignature(string $rawBody, string $signatureHeader, string $secret): bool
    {
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Verify + decode an inbound webhook delivery into a typed
     * {@see WebhookNotification}. Pass the RAW request body; supply the
     * `X-Okta-Signature` header and your subscription secret to have the HMAC
     * checked first (strongly recommended — omit both only when you verify out
     * of band).
     *
     *   $hook = Webhooks::parse(file_get_contents('php://input'),
     *       $_SERVER['HTTP_X_OKTA_SIGNATURE'] ?? '', $secret);
     *   match ($hook->type()) {
     *       WebhookEvent::MessageSent     => reactToSend($hook),
     *       WebhookEvent::MessageReceived => reactToInbound($hook),
     *       default => null,
     *   };
     *
     * @throws WhatsAppException On a signature mismatch or a body that isn't a
     *                           JSON object.
     */
    public static function parse(string $rawBody, ?string $signatureHeader = null, ?string $secret = null): WebhookNotification
    {
        if ($secret !== null) {
            if ($signatureHeader === null || ! self::verifySignature($rawBody, $signatureHeader, $secret)) {
                throw new WhatsAppException('Webhook signature verification failed.', 400);
            }
        }

        /** @var mixed $decoded */
        $decoded = json_decode($rawBody, true);

        if (! is_array($decoded)) {
            throw new WhatsAppException('Webhook body is not a JSON object.', 400);
        }

        /** @var array<string, mixed> $decoded */
        return WebhookNotification::fromArray($decoded);
    }
}
