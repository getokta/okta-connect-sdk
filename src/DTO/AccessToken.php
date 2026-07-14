<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * The token minted by the OAuth-style connect flow — the result of
 * exchanging a one-time authorization code at POST /api/v1/oauth/token.
 *
 * Feed `$token->accessToken` straight into a Client:
 *
 *   $token  = (new Connect($baseUrl))->exchange($code, $redirectUri);
 *   $client = new Client($baseUrl, $token->accessToken);
 */
final class AccessToken
{
    /**
     * @param  list<string>          $abilities
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly array $abilities,
        public readonly ?string $expiresAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['access_token', 'token_type', 'abilities', 'expires_at'];
        $extra = array_diff_key($data, array_flip($known));

        $abilities = [];

        if (isset($data['abilities']) && is_array($data['abilities'])) {
            foreach ($data['abilities'] as $ability) {
                $abilities[] = (string) $ability;
            }
        }

        return new self(
            accessToken: isset($data['access_token']) ? (string) $data['access_token'] : '',
            tokenType: isset($data['token_type']) ? (string) $data['token_type'] : 'Bearer',
            abilities: $abilities,
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            extra: $extra,
        );
    }

    /**
     * True when the token grants the given ability (as authorized on the
     * consent screen).
     */
    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'access_token' => $this->accessToken !== '' ? $this->accessToken : null,
            'token_type' => $this->tokenType,
            'abilities' => $this->abilities !== [] ? $this->abilities : null,
            'expires_at' => $this->expiresAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
