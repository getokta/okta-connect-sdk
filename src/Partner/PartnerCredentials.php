<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner;

use InvalidArgumentException;

/**
 * How a partner proves who it is.
 *
 * Two forms, matching what `/app/partner` hands out:
 *
 *   - **Key pair** (`client_id` + `client_secret`) — production. The SDK
 *     exchanges it for a short-lived bearer token and re-exchanges when
 *     that token nears expiry, so your code never sees the rotation.
 *   - **Static token** — a long-lived bearer for local development. Used
 *     verbatim; there is nothing to exchange and nothing to refresh.
 *
 * `abilities` narrows what the exchanged token may do relative to the key
 * that produced it. It can only ever narrow: asking for more than the key
 * holds is refused server-side. Leave it null to inherit the key's set.
 */
final class PartnerCredentials
{
    /**
     * @param  list<string>|null  $abilities
     */
    private function __construct(
        public readonly ?string $clientId,
        public readonly ?string $clientSecret,
        public readonly ?string $staticToken,
        public readonly ?array $abilities,
    ) {
    }

    /**
     * @param  list<string>|null  $abilities  Narrow the exchanged token; never widens.
     */
    public static function keyPair(string $clientId, string $clientSecret, ?array $abilities = null): self
    {
        if (trim($clientId) === '' || trim($clientSecret) === '') {
            throw new InvalidArgumentException('client_id and client_secret must both be non-empty.');
        }

        return new self(trim($clientId), trim($clientSecret), null, $abilities);
    }

    public static function staticToken(string $token): self
    {
        if (trim($token) === '') {
            throw new InvalidArgumentException('static partner token must not be empty.');
        }

        return new self(null, null, trim($token), null);
    }

    /**
     * True when the SDK can mint a fresh bearer on its own — the difference
     * between "this token expired, get another" and "this token expired,
     * tell the caller".
     */
    public function isExchangeable(): bool
    {
        return $this->clientId !== null && $this->clientSecret !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeBody(): array
    {
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $this->clientId,
            'client_secret' => (string) $this->clientSecret,
        ];

        if ($this->abilities !== null) {
            $body['abilities'] = $this->abilities;
        }

        return $body;
    }
}
