<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Webhook;

/**
 * Typed view over a channel.* webhook payload (connected/disconnected/deleted).
 */
final class ChannelEvent extends WebhookEventData
{
    public function channelId(): ?string
    {
        return $this->str('channel_id');
    }

    public function type(): ?string
    {
        return $this->str('type');
    }

    public function displayName(): ?string
    {
        return $this->str('display_name');
    }

    public function status(): ?string
    {
        return $this->str('status');
    }

    public function previousStatus(): ?string
    {
        return $this->str('previous_status');
    }
}
