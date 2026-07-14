<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Connect;

use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\DTO\AccessToken;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;
use Okta\Connect\WhatsApp\Http\HttpClient;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;

/**
 * OAuth-style "connect with one click" flow — the easy way to obtain an API
 * token for a user's organization without them copying a token by hand.
 *
 *   1. Send the user to authorizationUrl():
 *
 *        $state = Connect::generateState();       // store in session
 *        // ...
 *        $url = (new Connect('https://connect.getokta.io'))->authorizationUrl(
 *            appName:     'My CRM',
 *            redirectUri: 'https://crm.example.com/oktawa/callback',
 *            abilities:   ['read', 'send'],
 *            state:       $state,
 *        );
 *        return redirect()->away($url);
 *
 *   2. The platform shows the consent screen; on approval it redirects back to
 *      your redirect_uri with `?code=...&state=...` (or `?error=access_denied`).
 *      Exchange the one-time code for a token — pass the SAME redirect_uri:
 *
 *        $token  = $connect->handleCallback($_GET, $redirectUri, $state);
 *        $client = new Client($baseUrl, $token->accessToken);
 *
 * No client_secret and no PKCE: the authorization code is one-time,
 * expires in 5 minutes, and is bound to the redirect_uri, so an intercepted
 * redirect can't be redeemed elsewhere. Keep `state` opaque + unguessable and
 * verify it on return (handleCallback does) to defend against CSRF.
 */
final class Connect
{
    private readonly string $baseUrl;

    private readonly HttpClientInterface $http;

    /**
     * The abilities the consent screen understands. Anything else is dropped
     * server-side, so the builder filters here too for an early, obvious fail.
     *
     * @var list<string>
     */
    private const KNOWN_ABILITIES = ['read', 'write', 'send', 'admin'];

    public function __construct(string $baseUrl, ?HttpClientInterface $http = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        // The exchange endpoint is unauthenticated (it MINTS the token), so a
        // token-less transport is correct here.
        $this->http = $http ?? new HttpClient(new Config(baseUrl: $this->baseUrl, token: ''));
    }

    /**
     * Build the consent-screen URL to send the user to.
     *
     * @param  list<string>  $abilities  Subset of read/write/send/admin.
     * @param  string|null   $state      Opaque CSRF token — store it and verify
     *                                   it on the callback (see handleCallback).
     */
    public function authorizationUrl(
        string $appName,
        string $redirectUri,
        array $abilities = ['read'],
        ?string $state = null,
    ): string {
        $abilities = array_values(array_filter(
            $abilities,
            static fn (string $ability): bool => in_array($ability, self::KNOWN_ABILITIES, true),
        ));

        $params = [
            'app_name' => $appName,
            'redirect_uri' => $redirectUri,
            'abilities' => implode(',', $abilities !== [] ? $abilities : ['read']),
        ];

        if ($state !== null && $state !== '') {
            $params['state'] = $state;
        }

        return $this->baseUrl.'/connect?'.http_build_query($params);
    }

    /**
     * Generate an opaque, unguessable `state` value for CSRF protection.
     * Store it (e.g. in the session) before redirecting, then hand it to
     * handleCallback() on return.
     */
    public static function generateState(int $bytes = 24): string
    {
        return bin2hex(random_bytes(max(16, $bytes)));
    }

    /**
     * Validate the redirect query and exchange the code for an access token.
     *
     * @param  array<string, mixed>  $query         The callback query params
     *                                              (e.g. $_GET or $request->query()).
     * @param  string                $redirectUri   MUST equal the redirect_uri you
     *                                              authorized with.
     * @param  string|null           $expectedState The state you generated; when
     *                                              provided it must match or the
     *                                              call is rejected as CSRF.
     *
     * @throws WhatsAppException When the user denied consent, the state does not
     *                           match, or no code is present.
     */
    public function handleCallback(array $query, string $redirectUri, ?string $expectedState = null): AccessToken
    {
        $error = isset($query['error']) ? (string) $query['error'] : '';

        if ($error !== '') {
            throw new WhatsAppException('Authorization was not granted: '.$error, 400);
        }

        if ($expectedState !== null) {
            $returnedState = isset($query['state']) ? (string) $query['state'] : '';

            if (! hash_equals($expectedState, $returnedState)) {
                throw new WhatsAppException('OAuth state mismatch — possible CSRF; discarding the callback.', 400);
            }
        }

        $code = isset($query['code']) ? (string) $query['code'] : '';

        if ($code === '') {
            throw new WhatsAppException('The authorization callback carried no code.', 400);
        }

        return $this->exchange($code, $redirectUri);
    }

    /**
     * Exchange a one-time authorization code for an access token via
     * POST /api/v1/oauth/token. Pass the SAME redirect_uri that was authorized.
     *
     * @throws WhatsAppException On an invalid, used, or expired code (HTTP 400),
     *                           or any transport error.
     */
    public function exchange(string $code, string $redirectUri): AccessToken
    {
        $response = $this->http->post('/api/v1/oauth/token', [
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        $payload = $response->json();
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        /** @var array<string, mixed> $data */
        return AccessToken::fromArray($data);
    }
}
