<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\DTO\EmbedConfig;
use Okta\Connect\WhatsApp\DTO\EmbedOrigins;
use Okta\Connect\WhatsApp\DTO\EmbedSecret;

/**
 * Your embed signing key and the origins allowed to frame the inbox.
 *
 * The key is bound to your partner organization, and that binding is what
 * makes handing it to you defensible: a token signed with it resolves only
 * to a user who already exists and is an active member of a workspace you
 * manage. It cannot create an account, cannot grant a role, and cannot name
 * anyone else's workspace. The platform-wide embed secrets an operator
 * holds do all three, which is why you are not given one.
 *
 * Ability: `embed.manage`.
 */
final class PartnerEmbed extends PartnerResource
{
    /** Read the configuration. Never returns the secret — nothing can. */
    public function show(): EmbedConfig
    {
        $response = $this->http->get($this->partner('/embed'));

        return EmbedConfig::fromArray($response->json());
    }

    /**
     * Issue or rotate the key. The secret comes back exactly once.
     *
     * Rotation does not overlap: the previous secret stops verifying the
     * moment this returns, so re-mint live iframe tokens afterwards.
     *
     * @param  list<string>|null  $origins  Omit to keep the registered list.
     */
    public function issueSecret(?array $origins = null): EmbedSecret
    {
        $body = $origins === null ? [] : ['origins' => $origins];

        $response = $this->http->post($this->partner('/embed/secret'), $body);

        return EmbedSecret::fromArray($response->json());
    }

    /**
     * Replace the framing allowlist wholesale.
     *
     * Check {@see EmbedOrigins::$rejected} — an origin that did not parse is
     * absent from the stored list, and the symptom arrives as a blank iframe
     * with a 200 and no failed request.
     *
     * @param  list<string>  $origins
     */
    public function setOrigins(array $origins): EmbedOrigins
    {
        $response = $this->http->put($this->partner('/embed/origins'), ['origins' => $origins]);

        return EmbedOrigins::fromArray($response->json());
    }
}
