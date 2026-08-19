<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\DTO\Channel;

/**
 * Channels inside a workspace you manage.
 *
 * Creating one reserves a stable id you can reference; it does **not** give
 * you the customer's provider credentials. Those are attached later by the
 * workspace's own people through the dashboard OAuth flows, and a partner
 * never sees them.
 *
 * WhatsApp QR pairing is deliberately not here: it runs with a *workspace*
 * token (the kind {@see PartnerWorkspaceTokens} mints), because the channel
 * belongs to the workspace, not to you.
 *
 * Abilities: `channels.read` / `channels.write`.
 */
final class PartnerWorkspaceChannels extends PartnerResource
{
    /**
     * @return list<Channel>
     */
    public function list(string $workspaceUlid): array
    {
        $response = $this->http->get($this->workspacePath($workspaceUlid, '/channels'));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $rows = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        $channels = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $channels[] = Channel::fromArray($row);
                }
            }
        }

        return $channels;
    }

    /**
     * Reserve a channel. It lands in `disconnected` with no credentials —
     * that is the intended end state of this call, not a failure.
     *
     * @param  array<string, mixed>  $extra  e.g. phone_number
     */
    public function create(string $workspaceUlid, string $displayName, string $type, array $extra = []): Channel
    {
        $response = $this->http->post(
            $this->workspacePath($workspaceUlid, '/channels'),
            ['display_name' => $displayName, 'type' => $type] + $extra,
        );

        return Channel::fromArray($this->unwrap($response->json()));
    }
}
