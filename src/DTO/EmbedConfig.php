<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * `GET /api/v1/partner/embed` — the state of your embed key, minus the
 * secret, which the platform stores only as a verifier and cannot return.
 *
 * `issuer` is the value your JWTs must carry as `iss`. Read it from here
 * rather than assembling it: a token signed under the wrong issuer is
 * rejected server-side and shows up in the browser as an inbox that never
 * logs in, with a 200 on the network tab.
 */
final class EmbedConfig
{
    /**
     * @param  list<string>  $origins
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly bool $issued,
        public readonly ?string $secretId,
        public readonly ?string $issuer,
        public readonly ?string $audience,
        public readonly array $origins,
        public readonly ?string $issuedAt,
        public readonly array $extra = [],
    ) {}

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
            issued: (bool) ($payload['issued'] ?? false),
            secretId: isset($payload['secret_id']) ? (string) $payload['secret_id'] : null,
            issuer: isset($payload['issuer']) ? (string) $payload['issuer'] : null,
            audience: isset($payload['audience']) ? (string) $payload['audience'] : null,
            origins: $origins,
            issuedAt: isset($payload['issued_at']) ? (string) $payload['issued_at'] : null,
            extra: array_diff_key($payload, array_flip([
                'issued', 'secret_id', 'issuer', 'audience', 'origins', 'issued_at',
            ])),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'secret_id' => $this->secretId,
            'issuer' => $this->issuer,
            'audience' => $this->audience,
            'issued_at' => $this->issuedAt,
        ], static fn ($v): bool => $v !== null) + [
            'issued' => $this->issued,
            'origins' => $this->origins,
        ] + $this->extra;
    }
}
