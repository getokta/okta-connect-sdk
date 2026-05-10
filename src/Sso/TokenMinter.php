<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Sso;

use InvalidArgumentException;

/**
 * Mints HS256 JWTs accepted by the platform's `/embed/sso` route.
 *
 * Pure cryptography — no HTTP. Construct with the shared secret
 * returned by `AdminClient::embedSecret()->sync()` (or rotated
 * manually), then either:
 *
 *   - `mint()` to get the bare JWT string, or
 *   - `ssoUrl()` to get a ready-to-redirect-to URL with the token and
 *     a safe `?redirect=` target already encoded.
 *
 * Claims emitted match what `App\Modules\Platform\Application\Services\EmbedSsoVerifier`
 * expects on the platform side: `iss`, `aud`, `sub`, `email`, `name`,
 * `scope`, `jti`, `iat`, `exp`. Default `iss=okta-web`, `aud=okta-whatsapp`
 * — override only if the platform deployment uses different values.
 */
final class TokenMinter
{
    public function __construct(
        private readonly string $sharedSecret,
        private readonly string $issuer = 'okta-web',
        private readonly string $audience = 'okta-whatsapp',
        private readonly int $ttlSeconds = 60,
    ) {
        if ($sharedSecret === '') {
            throw new InvalidArgumentException('shared secret must not be empty');
        }

        if ($ttlSeconds < 1 || $ttlSeconds > 300) {
            throw new InvalidArgumentException('ttl must be between 1 and 300 seconds');
        }
    }

    /**
     * Build a signed JWT for the given subject. `scope` controls which
     * embedded surfaces the platform will allow:
     *
     *   - `platform.admin`  full /embed/* access
     *   - `platform.inbox`  /embed/inbox/* only
     *
     * @param  array<string, mixed>  $extraClaims
     */
    public function mint(
        string $subject,
        string $email,
        string $name,
        string $scope = 'platform.admin',
        array $extraClaims = [],
    ): string {
        if ($subject === '' || $email === '') {
            throw new InvalidArgumentException('subject and email are required');
        }

        $now = time();
        $payload = $extraClaims + [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $subject,
            'email' => $email,
            'name' => $name,
            'scope' => $scope,
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ];

        return $this->encode($payload);
    }

    /**
     * Convenience: mint + assemble the full `/embed/sso?token=...&redirect=...`
     * URL ready to drop into an `<iframe src="...">` or a `Location` header.
     */
    public function ssoUrl(
        string $baseUrl,
        string $subject,
        string $email,
        string $name,
        string $redirectPath,
        string $scope = 'platform.admin',
    ): string {
        $token = $this->mint($subject, $email, $name, $scope);

        return rtrim($baseUrl, '/').'/embed/sso?token='.rawurlencode($token).'&redirect='.rawurlencode($redirectPath);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->b64('{"alg":"HS256","typ":"JWT"}');
        $body = $this->b64((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $signature = hash_hmac('sha256', $header.'.'.$body, $this->sharedSecret, true);

        return $header.'.'.$body.'.'.$this->b64($signature);
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
