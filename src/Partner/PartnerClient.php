<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner;

use Okta\Connect\WhatsApp\Client;
use Okta\Connect\WhatsApp\DTO\PartnerIdentity;
use Okta\Connect\WhatsApp\DTO\WorkspaceToken;
use Okta\Connect\WhatsApp\Embed\Embed;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;
use Okta\Connect\WhatsApp\Partner\Resources\PartnerEmbed;
use Okta\Connect\WhatsApp\Partner\Resources\PartnerSso;
use Okta\Connect\WhatsApp\Partner\Resources\PartnerWorkspaceChannels;
use Okta\Connect\WhatsApp\Partner\Resources\PartnerWorkspaces;
use Okta\Connect\WhatsApp\Partner\Resources\PartnerWorkspaceTokens;
use Okta\Connect\WhatsApp\Partner\Resources\PartnerWorkspaceUsers;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * The Partner API client — the surface a **technical partner** (شريك تقني)
 * uses to wire Okta Connect into its own product.
 *
 * Separate from {@see Client} on purpose, and not merely for tidiness: a
 * partner token is bound to the partner organization rather than to a
 * tenant, lives in its own store, and is rejected by the tenant API exactly
 * as a tenant token is rejected here. One object per credential keeps that
 * boundary visible instead of turning it into a 401 that reads like a bug.
 *
 * ```php
 * $partner = PartnerClient::withKeyPair(
 *     'https://connect.getokta.io',
 *     $clientId,
 *     $clientSecret,
 * );
 *
 * // Idempotent on external_id — safe to re-run.
 * $result = $partner->workspaces()->create([
 *     'name'        => 'Acme Support',
 *     'external_id' => 'acct_8891',
 *     'owner'       => ['name' => 'Sara', 'email' => 'sara@acme.test', 'password_auto' => true],
 * ]);
 *
 * $token = $partner->tokens()->create(
 *     $result->workspace->id,
 *     'Acme product sync',
 *     $result->owner->id,
 *     ['read', 'send'],
 * );
 *
 * // …and the workspace's own data plane, with the token you just minted:
 * $workspace = $partner->workspaceClient($token);
 * ```
 *
 * Token lifetime is handled for you — see {@see PartnerTransport}.
 */
final class PartnerClient
{
    private readonly PartnerTransport $transport;

    private ?PartnerWorkspaces $workspaces = null;

    private ?PartnerWorkspaceUsers $users = null;

    private ?PartnerWorkspaceTokens $tokens = null;

    private ?PartnerWorkspaceChannels $channels = null;

    private ?PartnerSso $sso = null;

    private ?PartnerEmbed $embed = null;

    private ?PartnerIdentity $identity = null;

    /**
     * @param  array{timeout?: int, retries?: int, httpClient?: ClientInterface, userAgent?: string}  $options
     * @param  HttpClientInterface|null  $httpClient  Test seam.
     */
    public function __construct(
        private readonly string $baseUrl,
        PartnerCredentials $credentials,
        array $options = [],
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->transport = new PartnerTransport($baseUrl, $credentials, $options, $httpClient);
    }

    /**
     * Production: a key pair, exchanged for short-lived tokens under the hood.
     *
     * @param  array{timeout?: int, retries?: int, httpClient?: ClientInterface, userAgent?: string}  $options
     * @param  list<string>|null  $abilities  Narrow the minted token; never widens.
     */
    public static function withKeyPair(
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        array $options = [],
        ?array $abilities = null,
        ?HttpClientInterface $httpClient = null,
    ): self {
        return new self(
            $baseUrl,
            PartnerCredentials::keyPair($clientId, $clientSecret, $abilities),
            $options,
            $httpClient,
        );
    }

    /**
     * Development: the long-lived static token from `/app/partner`. Nothing
     * to exchange, nothing to refresh — and a 401 is the answer rather than
     * a trigger.
     *
     * @param  array{timeout?: int, retries?: int, httpClient?: ClientInterface, userAgent?: string}  $options
     */
    public static function withStaticToken(
        string $baseUrl,
        string $token,
        array $options = [],
        ?HttpClientInterface $httpClient = null,
    ): self {
        return new self($baseUrl, PartnerCredentials::staticToken($token), $options, $httpClient);
    }

    /**
     * Who this token belongs to and what it may do.
     *
     * Worth calling on boot: it is the cheapest proof a key is live and
     * carries the abilities the run is about to need — before that run
     * half-completes and leaves a workspace without its owner. Cached for
     * the life of this object; pass `$fresh` to re-read.
     */
    public function me(bool $fresh = false): PartnerIdentity
    {
        if ($fresh) {
            $this->identity = null;
        }

        return $this->identity ??= PartnerIdentity::fromArray(
            $this->transport->get('/api/v1/partner/me')->json(),
        );
    }

    public function workspaces(): PartnerWorkspaces
    {
        return $this->workspaces ??= new PartnerWorkspaces($this->transport);
    }

    public function users(): PartnerWorkspaceUsers
    {
        return $this->users ??= new PartnerWorkspaceUsers($this->transport);
    }

    public function tokens(): PartnerWorkspaceTokens
    {
        return $this->tokens ??= new PartnerWorkspaceTokens($this->transport);
    }

    public function channels(): PartnerWorkspaceChannels
    {
        return $this->channels ??= new PartnerWorkspaceChannels($this->transport);
    }

    public function sso(): PartnerSso
    {
        return $this->sso ??= new PartnerSso($this->transport);
    }

    public function embed(): PartnerEmbed
    {
        return $this->embed ??= new PartnerEmbed($this->transport);
    }

    /**
     * A tenant {@see Client} for one of your workspaces, using a token you
     * minted for it. Closes the loop: provision here, then act there,
     * without hand-assembling a second client and a second base URL.
     */
    public function workspaceClient(WorkspaceToken|string $token, array $options = []): Client
    {
        $plain = $token instanceof WorkspaceToken ? $token->plainTextToken : $token;

        if ($plain === null || $plain === '') {
            throw new RuntimeException(
                'This WorkspaceToken carries no plain text. The secret is returned only on the mint '
                .'response; a token read back from a list cannot be used to build a client.',
            );
        }

        return new Client($this->baseUrl, $plain, $options);
    }

    /**
     * An {@see Embed} signer already wired to **your** issuer.
     *
     * The issuer is derived, never typed: signing as `okta-web` is refused
     * server-side, and the browser shows it as an inbox that silently never
     * signs in. Pass the secret from {@see PartnerEmbed::issueSecret()}; the
     * issuer is read from `/embed` when you do not supply one.
     */
    public function embedSigner(string $secret, ?string $issuer = null, string $audience = 'okta-whatsapp'): Embed
    {
        $issuer ??= $this->embed()->show()->issuer ?? $this->me()->embedIssuer();

        if ($issuer === null || $issuer === '') {
            throw new RuntimeException(
                'No embed issuer available: issue an embed key first (POST /api/v1/partner/embed/secret).',
            );
        }

        return new Embed($this->baseUrl, $secret, $issuer, $audience);
    }

    /** The live bearer, exchanging one first if needed. */
    public function accessToken(): string
    {
        return $this->transport->accessToken();
    }

    public function http(): HttpClientInterface
    {
        return $this->transport;
    }
}
