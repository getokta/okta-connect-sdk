<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner;

use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\DTO\PartnerAccessToken;
use Okta\Connect\WhatsApp\Exceptions\AuthenticationException;
use Okta\Connect\WhatsApp\Http\HttpClient;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;
use Okta\Connect\WhatsApp\Http\Response;
use Psr\Http\Client\ClientInterface;

/**
 * The transport every Partner API call goes through: an HttpClientInterface
 * that keeps a live bearer token in front of it.
 *
 * Partner tokens live one hour. Everything that follows exists so that fact
 * never reaches your code:
 *
 *   - The first call exchanges the key pair for a token.
 *   - Subsequent calls reuse it until it is within {@see SKEW_SECONDS} of
 *     expiry, then re-exchange. One in-flight exchange at a time — a
 *     provisioning burst does not turn into a burst of `/token` calls, which
 *     is the endpoint with a 10/min limit.
 *   - A `401` that slips through anyway (clock skew, a token revoked
 *     server-side mid-run) triggers exactly one re-exchange and one retry.
 *     Twice would be a loop; the second failure is real and is raised.
 *
 * A static development token skips all of it: nothing to exchange, so a
 * `401` is the answer, not a trigger.
 */
final class PartnerTransport implements HttpClientInterface
{
    /**
     * Re-exchange this long before the server-stated expiry. Covers clock
     * drift between us and the platform, plus the flight time of the call
     * the token is about to authenticate.
     */
    private const SKEW_SECONDS = 60;

    private ?string $accessToken = null;

    private ?int $expiresAt = null;

    /** @var list<string> */
    private array $abilities = [];

    /** Transport bound to {@see $accessToken}; discarded when the token rotates. */
    private ?HttpClientInterface $bound = null;

    /** Guards against a refresh triggering a refresh. */
    private bool $exchanging = false;

    /**
     * @param  array{timeout?: int, retries?: int, httpClient?: ClientInterface, userAgent?: string}  $options
     * @param  HttpClientInterface|null  $override  Test seam: used verbatim for
     *                                              every call, token included.
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly PartnerCredentials $credentials,
        private readonly array $options = [],
        private readonly ?HttpClientInterface $override = null,
    ) {
        if ($credentials->staticToken !== null) {
            $this->accessToken = $credentials->staticToken;
        }
    }

    public function get(string $path, array $query = [], array $headers = []): Response
    {
        return $this->call(static fn (HttpClientInterface $http): Response => $http->get($path, $query, $headers));
    }

    public function post(string $path, array $body = [], array $headers = []): Response
    {
        return $this->call(static fn (HttpClientInterface $http): Response => $http->post($path, $body, $headers));
    }

    public function patch(string $path, array $body = [], array $headers = []): Response
    {
        return $this->call(static fn (HttpClientInterface $http): Response => $http->patch($path, $body, $headers));
    }

    public function put(string $path, array $body = [], array $headers = []): Response
    {
        return $this->call(static fn (HttpClientInterface $http): Response => $http->put($path, $body, $headers));
    }

    public function delete(string $path, array $body = [], array $headers = []): Response
    {
        return $this->call(static fn (HttpClientInterface $http): Response => $http->delete($path, $body, $headers));
    }

    /**
     * The bearer currently in use — exposed for logging and for handing the
     * same session to another tool. Forces an exchange if none is live yet.
     */
    public function accessToken(): string
    {
        $this->ensureToken();

        return (string) $this->accessToken;
    }

    /**
     * Abilities the platform granted the live token. Empty for a static
     * development token, whose abilities are only visible through `me()`.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        $this->ensureToken();

        return $this->abilities;
    }

    /** Unix timestamp the live token expires at, or null when it does not. */
    public function expiresAt(): ?int
    {
        $this->ensureToken();

        return $this->expiresAt;
    }

    /**
     * Drop the cached token so the next call mints a fresh one. Rarely
     * needed; the transport refreshes on its own.
     */
    public function forget(): void
    {
        if (! $this->credentials->isExchangeable()) {
            return;
        }

        $this->accessToken = null;
        $this->expiresAt = null;
        $this->abilities = [];
        $this->bound = null;
    }

    /**
     * @param  callable(HttpClientInterface): Response  $send
     */
    private function call(callable $send): Response
    {
        try {
            return $send($this->authorized());
        } catch (AuthenticationException $e) {
            // Only a key pair can answer a 401 by trying again, and only once.
            if (! $this->credentials->isExchangeable() || $this->exchanging) {
                throw $e;
            }

            $this->forget();

            return $send($this->authorized());
        }
    }

    private function authorized(): HttpClientInterface
    {
        $this->ensureToken();

        if ($this->override !== null) {
            return $this->override;
        }

        return $this->bound ??= $this->transportFor((string) $this->accessToken);
    }

    private function ensureToken(): void
    {
        if (! $this->credentials->isExchangeable()) {
            return;
        }

        if ($this->accessToken !== null && ! $this->isStale()) {
            return;
        }

        $this->exchange();
    }

    private function isStale(): bool
    {
        // No stated expiry means we cannot pre-empt one; the 401 retry is
        // what covers that case.
        if ($this->expiresAt === null) {
            return false;
        }

        return time() >= ($this->expiresAt - self::SKEW_SECONDS);
    }

    private function exchange(): void
    {
        $this->exchanging = true;

        try {
            // The key pair in the body IS the credential here, so this one
            // call goes out with no bearer attached.
            $response = $this->transportFor('')->post(
                '/api/v1/partner/token',
                $this->credentials->exchangeBody(),
            );

            $token = PartnerAccessToken::fromArray($response->json());

            $this->accessToken = $token->accessToken;
            $this->abilities = $token->abilities;
            $this->expiresAt = $token->expiresAtTimestamp();
            $this->bound = null;
        } finally {
            $this->exchanging = false;
        }
    }

    private function transportFor(string $token): HttpClientInterface
    {
        if ($this->override !== null) {
            return $this->override;
        }

        return new HttpClient(new Config(
            baseUrl: $this->baseUrl,
            token: $token,
            timeout: $this->options['timeout'] ?? 30,
            retries: $this->options['retries'] ?? 2,
            httpClient: $this->options['httpClient'] ?? null,
            userAgent: $this->options['userAgent'] ?? 'okta-connect-sdk-php/2.2',
        ));
    }
}
