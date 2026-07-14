<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Webhook;

use Okta\Connect\WhatsApp\DTO\WebhookNotification;
use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use Okta\Connect\WhatsApp\Webhook\WebhookRouter;
use PHPUnit\Framework\TestCase;

final class WebhookRouterTest extends TestCase
{
    /** @param array<string, mixed> $payload */
    private function body(string $event, array $payload = []): string
    {
        return (string) json_encode(['event' => $event, 'organization_id' => 1, 'payload' => $payload]);
    }

    public function test_dispatch_runs_exact_family_and_global_handlers(): void
    {
        $hits = [];
        $router = (new WebhookRouter)
            ->on(WebhookEvent::MessageSent, function () use (&$hits): void { $hits[] = 'exact'; })
            ->onMessage(function () use (&$hits): void { $hits[] = 'family'; })
            ->onAny(function () use (&$hits): void { $hits[] = 'any'; });

        $hook = $router->dispatch($this->body('message.sent', ['message' => ['body' => 'hi']]));

        $this->assertSame(['exact', 'family', 'any'], $hits);
        $this->assertInstanceOf(WebhookNotification::class, $hook);
        $this->assertSame('hi', $hook->message()?->body());
    }

    public function test_dispatch_verifies_signature_when_secret_given(): void
    {
        $secret = 'sekret';
        $body = $this->body('channel.deleted', ['channel_id' => 'ch_1', 'type' => 'telegram']);
        $sig = 'sha256='.hash_hmac('sha256', $body, $secret);

        $seen = null;
        (new WebhookRouter($secret))
            ->onChannel(function (WebhookNotification $h) use (&$seen): void { $seen = $h->channel()?->type(); })
            ->dispatch($body, $sig);

        $this->assertSame('telegram', $seen);
    }

    public function test_dispatch_throws_on_bad_signature(): void
    {
        $this->expectException(\Okta\Connect\WhatsApp\Exceptions\WhatsAppException::class);
        (new WebhookRouter('sekret'))->dispatch($this->body('message.sent'), 'sha256=bad');
    }

    public function test_typed_views_are_null_for_the_wrong_family(): void
    {
        $router = new WebhookRouter;
        $hook = $router->dispatch($this->body('subscription.expired', ['status' => 'expired', 'plan' => 'pro']));

        $this->assertNull($hook->message());
        $this->assertNull($hook->channel());
        $this->assertSame('expired', $hook->subscription()?->status());
        $this->assertSame('pro', $hook->subscription()?->planKey());
    }
}
