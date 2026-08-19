<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * The one and only time you see an embed signing secret.
 *
 * Issued by `POST /api/v1/partner/embed/secret`. Connect keeps a verifier,
 * not the secret, so a lost value is re-issued — and re-issuing invalidates
 * every token minted with the old one, because rotation deliberately does
 * not overlap.
 */
final class EmbedSecret
{
    /**
     * @param  list<string>          $origins
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $secret,
        public readonly ?string $secretId,
        public readonly ?string $issuer,
        public readonly ?string $audience,
        public readonly array $origins,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

        $origins = [];

        if (isset($payload['origins']) && is_array($payload['origins'])) {
            foreach ($payload['origins'] as $origin) {
                $origins[] = (string) $origin;
            }
        }

        return new self(
            secret: (string) ($payload['secret'] ?? ''),
            secretId: isset($payload['secret_id']) ? (string) $payload['secret_id'] : null,
            issuer: isset($payload['issuer']) ? (string) $payload['issuer'] : null,
            audience: isset($payload['audience']) ? (string) $payload['audience'] : null,
            origins: $origins,
            extra: array_diff_key($payload, array_flip([
                'secret', 'secret_id', 'issuer', 'audience', 'origins',
            ])),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'secret' => $this->secret,
            'secret_id' => $this->secretId,
            'issuer' => $this->issuer,
            'audience' => $this->audience,
        ], static fn ($v): bool => $v !== null && $v !== '') + ['origins' => $this->origins] + $this->extra;
    }
}
