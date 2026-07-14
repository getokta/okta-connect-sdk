<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Enums;

use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use PHPUnit\Framework\TestCase;

final class WebhookEventTest extends TestCase
{
    /** Values must stay identical to the platform's dotted event strings. */
    public function test_values_match_platform_events(): void
    {
        $this->assertSame('*', WebhookEvent::All->value);
        $this->assertSame('subscription.expired', WebhookEvent::SubscriptionExpired->value);
        $this->assertSame('subscription.cancelled', WebhookEvent::SubscriptionCancelled->value);
        $this->assertSame('subscription.activated', WebhookEvent::SubscriptionActivated->value);
        $this->assertSame('subscription.past_due', WebhookEvent::SubscriptionPastDue->value);
        $this->assertSame('channel.deleted', WebhookEvent::ChannelDeleted->value);
        $this->assertSame('channel.disconnected', WebhookEvent::ChannelDisconnected->value);
        $this->assertSame('channel.connected', WebhookEvent::ChannelConnected->value);
    }

    public function test_try_from_resolves_a_delivered_event_name(): void
    {
        $this->assertSame(WebhookEvent::ChannelDeleted, WebhookEvent::tryFrom('channel.deleted'));
        $this->assertNull(WebhookEvent::tryFrom('not.an.event'));
    }

    public function test_channel_lifecycle_grouping(): void
    {
        $this->assertTrue(WebhookEvent::ChannelConnected->isChannelLifecycle());
        $this->assertTrue(WebhookEvent::ChannelDeleted->isChannelLifecycle());
        $this->assertFalse(WebhookEvent::SubscriptionExpired->isChannelLifecycle());
    }

    public function test_subscription_grouping(): void
    {
        $this->assertTrue(WebhookEvent::SubscriptionExpired->isSubscription());
        $this->assertTrue(WebhookEvent::SubscriptionCancelled->isSubscription());
        $this->assertFalse(WebhookEvent::ChannelConnected->isSubscription());
    }
}
