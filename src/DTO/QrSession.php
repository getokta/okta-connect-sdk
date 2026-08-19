<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * One cycle of a WhatsApp QR pairing session.
 *
 * `qr` is null until the gateway mints a code and again once the attempt
 * reaches a terminal state; `qrTtlSeconds` is how long the current code
 * stays valid, which is what a countdown renders.
 *
 * **`error` is the field to branch on.** A status of `pending` or
 * `connecting` with `error === null` means "keep polling"; anything else
 * means the loop is waiting for something that is not coming:
 *
 *   - `gateway_unavailable` — the pairing gateway is unreachable or
 *     refusing. Retry; if it persists it is the platform's problem, not
 *     yours.
 *   - `pairing_failed` — the boot failed. Start a new session.
 *   - `qr_expired` — nobody scanned in time. Start a new session.
 *   - `disconnected` — the channel was linked and has since dropped.
 *
 * The platform emits both a flat shape (`id`, `status`, `expires_in`) and a
 * `channel` envelope (`qr_ttl_seconds`) carrying identical values, because
 * the two published SDK generations declared different ones. This DTO reads
 * whichever is present.
 */
final class QrSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $displayName,
        public readonly string $status,
        public readonly ?string $qr,
        public readonly ?int $qrTtlSeconds,
        public readonly ?string $error = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $channel */
        $channel = is_array($data['channel'] ?? null) ? $data['channel'] : [];

        $rawQr = $data['qr'] ?? null;
        $rawTtl = $data['qr_ttl_seconds'] ?? $data['expires_in'] ?? null;
        $rawError = $data['error'] ?? null;

        return new self(
            id: (string) ($channel['id'] ?? $data['id'] ?? $data['channel_id'] ?? ''),
            displayName: (string) ($channel['display_name'] ?? $data['display_name'] ?? ''),
            status: (string) ($channel['status'] ?? $data['status'] ?? 'pending'),
            qr: is_string($rawQr) && $rawQr !== '' ? $rawQr : null,
            qrTtlSeconds: is_int($rawTtl) || (is_string($rawTtl) && ctype_digit($rawTtl)) ? (int) $rawTtl : null,
            error: is_string($rawError) && $rawError !== '' ? $rawError : null,
        );
    }

    /**
     * Stop polling. `qr_expired` belongs here: the gateway has stopped
     * regenerating, so a loop that kept waiting on it would never end.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['connected', 'disconnected', 'failed', 'qr_expired'], true);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /** Something went wrong that more polling will not fix. */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Whether the failure is worth another attempt at all. A gateway that is
     * merely unreachable recovers; a session whose code expired or whose
     * boot failed needs a NEW session, not more polling of this one.
     */
    public function isRetryable(): bool
    {
        return $this->error === 'gateway_unavailable';
    }
}
