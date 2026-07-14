<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Webhook;

/**
 * Typed view over a subscription.* billing webhook payload
 * (activated/cancelled/expired/past_due).
 */
final class SubscriptionEvent extends WebhookEventData
{
    public function subscriptionId(): ?string
    {
        return $this->str('subscription_id') ?? $this->str('id');
    }

    public function status(): ?string
    {
        return $this->str('status');
    }

    public function planKey(): ?string
    {
        return $this->str('plan') ?? $this->str('plan_key');
    }

    public function currentPeriodEnd(): ?string
    {
        return $this->str('current_period_end') ?? $this->str('ends_at');
    }
}
