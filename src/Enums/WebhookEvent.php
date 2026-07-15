<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Enums;

/**
 * Outbound webhook event names, value-identical to the platform's dotted event
 * strings so `WebhookEvent::tryFrom($delivery['event'])` resolves. Pass the
 * `value`s when registering a subscription, e.g.:
 *
 *   $client->webhooks()->create([
 *       'name'   => 'Lifecycle',
 *       'url'    => 'https://example.test/hooks',
 *       'events' => [WebhookEvent::SubscriptionExpired->value, WebhookEvent::ChannelDeleted->value],
 *   ]);
 *
 * Use WebhookEvent::All ('*') to receive every event.
 */
enum WebhookEvent: string
{
    case All = '*';

    // Messaging
    case MessageReceived = 'message.received';
    case MessageSent = 'message.sent';
    case MessageDelivered = 'message.delivered';
    case MessageRead = 'message.read';
    case MessageFailed = 'message.failed';

    // Conversations
    case ConversationOpened = 'conversation.opened';
    case ConversationAssigned = 'conversation.assigned';
    case ConversationClosed = 'conversation.closed';

    // Channel lifecycle
    case ChannelConnected = 'channel.connected';
    case ChannelDisconnected = 'channel.disconnected';
    case ChannelDeleted = 'channel.deleted';

    // Subscription lifecycle (billing)
    case SubscriptionActivated = 'subscription.activated';
    case SubscriptionCancelled = 'subscription.cancelled';
    case SubscriptionExpired = 'subscription.expired';
    case SubscriptionPastDue = 'subscription.past_due';

    // Email delivery
    case EmailDelivered = 'email.delivered';
    case EmailBounced = 'email.bounced';
    case EmailComplained = 'email.complained';
    case EmailOpened = 'email.opened';
    case EmailClicked = 'email.clicked';

    // A connected app's access was revoked — by the app itself
    // (payload.source = "app") or by the workspace ("workspace").
    case ConnectionRevoked = 'connection.revoked';

    /** True for channel.connected / .disconnected / .deleted. */
    public function isChannelLifecycle(): bool
    {
        return in_array($this, [
            self::ChannelConnected,
            self::ChannelDisconnected,
            self::ChannelDeleted,
        ], true);
    }

    /** True for the subscription.* billing lifecycle events. */
    public function isSubscription(): bool
    {
        return in_array($this, [
            self::SubscriptionActivated,
            self::SubscriptionCancelled,
            self::SubscriptionExpired,
            self::SubscriptionPastDue,
        ], true);
    }
}
