<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Embed;

use InvalidArgumentException;

/**
 * One place for everything an integrating platform needs to embed the
 * Okta Connect inbox in an iframe. No HTTP — pure, dependency-free
 * HS256 crypto + URL assembly.
 *
 * Construct it with the shared secret provisioned server-side by your
 * platform operator (under the `embed.*` settings; legacy `iss=okta-web`,
 * or a per-partner issuer). Or, with an API client already in hand,
 * `$client->embed($secret)` fills in the base URL.
 *
 * Two flows — pick by how the iframe re-proves identity:
 *
 *   1. SSO landing handshake. Mint a ONE-SHOT token (≤5 min, replay-
 *      checked server-side) and point the iframe at `ssoUrl(...)`. The
 *      platform verifies it, starts a normal session, and bounces to
 *      `?redirect=`. Cookies must survive — fine on same-site, fragile
 *      in third-party-cookie-blocking browsers.
 *
 *   2. Cookieless per-request. Mint a LONG-LIVED token (≤4 h, no
 *      replay) and either point the iframe at `embedUrl(...)` (the
 *      token rides every request as `?embed_token=`) or attach
 *      `tokenHeader()` to your XHR/fetch calls. No cookie needed — the
 *      JWT itself re-authenticates each request. This is what survives
 *      Safari ITP / Chrome third-party-cookie phase-out, and the reason
 *      this class exists: the old `TokenMinter` capped TTL at 300 s and
 *      couldn't mint this token at all, so every platform hand-rolled
 *      its own — the source of the recurring embed breakage.
 *
 * Both flows accept the same `ui_hide` list (see {@see UiHide}); keys
 * are validated at mint time so a typo fails loudly here instead of
 * silently leaving a control visible in the iframe.
 */
final class Embed
{
    public const SCOPE_INBOX = 'platform.inbox';

    public const SCOPE_ADMIN = 'platform.admin';

    /** Server-side ceiling on the one-shot SSO token's lifetime. */
    private const SSO_MAX_TTL = 300;

    /** Server-side ceiling on the long-lived cookieless token's lifetime. */
    private const SESSION_MAX_TTL = 14400;

    private const DEFAULT_REDIRECT = '/app/inbox?embedded=1';

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $sharedSecret,
        private readonly string $issuer = 'okta-web',
        private readonly string $audience = 'okta-whatsapp',
    ) {
        if ($sharedSecret === '') {
            throw new InvalidArgumentException('embed shared secret must not be empty');
        }
    }

    // -----------------------------------------------------------------
    // Flow 1 — one-shot SSO landing handshake (≤5 min)
    // -----------------------------------------------------------------

    /**
     * Mint a one-shot SSO token for the `/embed/sso` landing route.
     *
     * @param  list<string>  $uiHide  Feature keys to strip (see {@see UiHide}).
     */
    public function ssoToken(
        EmbedUser $user,
        string $scope = self::SCOPE_ADMIN,
        array $uiHide = [],
        int $ttlSeconds = 60,
    ): string {
        $this->assertTtl($ttlSeconds, self::SSO_MAX_TTL);

        return $this->encode($user, $scope, $uiHide, $ttlSeconds);
    }

    /**
     * Mint + assemble the full `/embed/sso?token=...&redirect=...` URL,
     * ready to drop into an `<iframe src>` or a `Location` header.
     *
     * @param  list<string>  $uiHide
     */
    public function ssoUrl(
        EmbedUser $user,
        string $redirectPath = self::DEFAULT_REDIRECT,
        string $scope = self::SCOPE_ADMIN,
        array $uiHide = [],
        int $ttlSeconds = 60,
    ): string {
        $token = $this->ssoToken($user, $scope, $uiHide, $ttlSeconds);

        return $this->baseUrl()
            .'/embed/sso?token='.rawurlencode($token)
            .'&redirect='.rawurlencode($redirectPath);
    }

    // -----------------------------------------------------------------
    // Flow 2 — cookieless per-request token (≤4 h)
    // -----------------------------------------------------------------

    /**
     * Mint a long-lived token for cookieless per-request auth. The same
     * token rides every request inside the iframe; the platform does not
     * replay-check it.
     *
     * @param  list<string>  $uiHide
     */
    public function sessionToken(
        EmbedUser $user,
        string $scope = self::SCOPE_ADMIN,
        array $uiHide = [],
        int $ttlSeconds = self::SESSION_MAX_TTL,
    ): string {
        $this->assertTtl($ttlSeconds, self::SESSION_MAX_TTL);

        return $this->encode($user, $scope, $uiHide, $ttlSeconds);
    }

    /**
     * Build an iframe URL for `$path` with a freshly-minted session
     * token appended as `?embed_token=`. Existing query params on
     * `$path` are preserved.
     *
     * @param  list<string>  $uiHide
     */
    public function embedUrl(
        string $path,
        EmbedUser $user,
        string $scope = self::SCOPE_ADMIN,
        array $uiHide = [],
        int $ttlSeconds = self::SESSION_MAX_TTL,
    ): string {
        $token = $this->sessionToken($user, $scope, $uiHide, $ttlSeconds);

        return $this->appendQuery(
            $this->baseUrl().'/'.ltrim($path, '/'),
            'embed_token',
            $token,
        );
    }

    /**
     * Convenience preset: the embedded inbox URL with a cookieless
     * session token, scoped to `platform.inbox` by default.
     *
     * @param  list<string>  $uiHide
     */
    public function inboxUrl(
        EmbedUser $user,
        string $scope = self::SCOPE_INBOX,
        array $uiHide = [],
        int $ttlSeconds = self::SESSION_MAX_TTL,
    ): string {
        return $this->embedUrl(self::DEFAULT_REDIRECT, $user, $scope, $uiHide, $ttlSeconds);
    }

    /**
     * The header an XHR/fetch consumer attaches instead of putting the
     * token in the URL — handy when you'd rather not leak the JWT into
     * browser history / referrer logs.
     *
     * @return array{'X-Embed-Token': string}
     */
    public function tokenHeader(string $sessionToken): array
    {
        return ['X-Embed-Token' => $sessionToken];
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * @param  list<string>  $uiHide
     */
    private function encode(EmbedUser $user, string $scope, array $uiHide, int $ttlSeconds): string
    {
        if (! in_array($scope, [self::SCOPE_INBOX, self::SCOPE_ADMIN], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown scope "%s". Use Embed::SCOPE_INBOX or Embed::SCOPE_ADMIN.',
                $scope,
            ));
        }

        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $user->sub,
            'email' => $user->email,
            'name' => $user->name,
            'scope' => $scope,
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ];

        $cleanUiHide = UiHide::validate($uiHide);

        if ($cleanUiHide !== []) {
            $payload['ui_hide'] = $cleanUiHide;
        }

        $header = $this->b64('{"alg":"HS256","typ":"JWT"}');
        $body = $this->b64((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $signature = hash_hmac('sha256', $header.'.'.$body, $this->sharedSecret, true);

        return $header.'.'.$body.'.'.$this->b64($signature);
    }

    private function assertTtl(int $ttlSeconds, int $max): void
    {
        if ($ttlSeconds < 1 || $ttlSeconds > $max) {
            throw new InvalidArgumentException(sprintf('ttl must be between 1 and %d seconds', $max));
        }
    }

    private function appendQuery(string $url, string $key, string $value): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.rawurlencode($key).'='.rawurlencode($value);
    }

    private function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
