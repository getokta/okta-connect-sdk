<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A one-time sign-in link from `POST /workspaces/{id}/sso`.
 *
 * The ticket in the URL is opaque, single-use (redeeming deletes it) and
 * expires in five minutes — so hand it straight to the browser. Storing it,
 * mailing it, or putting it anywhere that keeps a history buys nothing: by
 * the time anyone finds it, it is dead.
 */
final class SsoLink
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $url,
        public readonly ?int $expiresIn,
        public readonly array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

        return new self(
            url: (string) ($payload['url'] ?? ''),
            expiresIn: isset($payload['expires_in']) && is_numeric($payload['expires_in'])
                ? (int) $payload['expires_in']
                : null,
            extra: array_diff_key($payload, array_flip(['url', 'expires_in'])),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'expires_in' => $this->expiresIn,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
