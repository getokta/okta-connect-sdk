<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * One poll cycle of a QR pairing session, as returned by
 * `GET /api/integrations/qr/sessions/{ulid}`.
 *
 * `qr` is null once the channel is paired or in a terminal state; partner
 * UIs should stop polling when status hits 'connected', 'disconnected'
 * or 'failed'. `qrTtlSeconds` is the time the current code remains valid
 * — useful for rendering a countdown.
 */
final readonly class QrSession
{
    public function __construct(
        public string $id,
        public string $displayName,
        public string $status,
        public ?string $qr,
        public ?int $qrTtlSeconds,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $channel */
        $channel = is_array($data['channel'] ?? null) ? $data['channel'] : [];

        $rawQr = $data['qr'] ?? null;
        $rawTtl = $data['qr_ttl_seconds'] ?? null;

        return new self(
            id: (string) ($channel['id'] ?? ''),
            displayName: (string) ($channel['display_name'] ?? ''),
            status: (string) ($channel['status'] ?? 'pending'),
            qr: is_string($rawQr) && $rawQr !== '' ? $rawQr : null,
            qrTtlSeconds: is_int($rawTtl) || (is_string($rawTtl) && ctype_digit($rawTtl)) ? (int) $rawTtl : null,
        );
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['connected', 'disconnected', 'failed'], true);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}
