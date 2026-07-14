<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Webhook;

/**
 * Typed view over a message.* webhook payload — which message, in which
 * conversation, on which channel, and whether it's a reply.
 */
final class MessageEvent extends WebhookEventData
{
    public function messageId(): ?string
    {
        return $this->str('message.id');
    }

    /** 'in' (received) or 'out' (sent). */
    public function direction(): ?string
    {
        return $this->str('message.direction');
    }

    public function type(): ?string
    {
        return $this->str('message.type');
    }

    public function body(): ?string
    {
        return $this->str('message.body');
    }

    /** queued|sent|delivered|read|failed. */
    public function status(): ?string
    {
        return $this->str('message.status');
    }

    public function isReply(): bool
    {
        return $this->bool('message.is_reply');
    }

    public function replyToMessageId(): ?string
    {
        return $this->str('message.reply_to.message_id');
    }

    public function providerMessageId(): ?string
    {
        return $this->str('message.provider_message_id');
    }

    public function conversationId(): ?string
    {
        return $this->str('conversation.id');
    }

    /** dm|comment|mention. */
    public function conversationKind(): ?string
    {
        return $this->str('conversation.kind');
    }

    public function channelId(): ?string
    {
        return $this->str('channel.id');
    }

    public function channelType(): ?string
    {
        return $this->str('channel.type');
    }

    public function channelName(): ?string
    {
        return $this->str('channel.name');
    }

    public function contactName(): ?string
    {
        return $this->str('contact.name');
    }

    public function contactPhone(): ?string
    {
        return $this->str('contact.phone');
    }
}
