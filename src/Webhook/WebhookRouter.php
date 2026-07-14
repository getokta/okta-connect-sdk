<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Webhook;

use Okta\Connect\WhatsApp\DTO\WebhookNotification;
use Okta\Connect\WhatsApp\Enums\WebhookEvent;
use Okta\Connect\WhatsApp\Resources\Webhooks;

/**
 * Register one handler per event (or per family) and dispatch an inbound
 * delivery to the right one — cleaner than a big match on $hook->type().
 *
 *   $router = (new WebhookRouter($secret))
 *       ->on(WebhookEvent::MessageReceived, fn (WebhookNotification $h) => reply($h))
 *       ->onMessage(fn ($h) => log($h))              // any message.* event
 *       ->on(WebhookEvent::ChannelDeleted, fn ($h) => teardown($h))
 *       ->onAny(fn ($h) => audit($h));               // fallback for the rest
 *
 *   $router->dispatch(file_get_contents('php://input'),
 *       $_SERVER['HTTP_X_OKTA_SIGNATURE'] ?? '');
 *
 * Every matching handler runs (exact event, then its `family.*`, then `*`).
 * Handlers receive the parsed {@see WebhookNotification}.
 */
final class WebhookRouter
{
    /** @var array<string, list<callable(WebhookNotification): mixed>> */
    private array $handlers = [];

    /**
     * @param  string|null  $secret  When set, dispatch() verifies the HMAC and
     *                               throws on a bad signature. Omit only when
     *                               you verify out of band.
     */
    public function __construct(private readonly ?string $secret = null)
    {
    }

    /**
     * @param  callable(WebhookNotification): mixed  $handler
     */
    public function on(WebhookEvent|string $event, callable $handler): self
    {
        $key = $event instanceof WebhookEvent ? $event->value : $event;
        $this->handlers[$key][] = $handler;

        return $this;
    }

    /** @param callable(WebhookNotification): mixed $handler */
    public function onMessage(callable $handler): self
    {
        return $this->on('message.*', $handler);
    }

    /** @param callable(WebhookNotification): mixed $handler */
    public function onChannel(callable $handler): self
    {
        return $this->on('channel.*', $handler);
    }

    /** @param callable(WebhookNotification): mixed $handler */
    public function onSubscription(callable $handler): self
    {
        return $this->on('subscription.*', $handler);
    }

    /** @param callable(WebhookNotification): mixed $handler */
    public function onEmail(callable $handler): self
    {
        return $this->on('email.*', $handler);
    }

    /** Fallback for any event without a more specific handler. */
    public function onAny(callable $handler): self
    {
        return $this->on('*', $handler);
    }

    /**
     * Verify + parse the delivery, then run every matching handler. Returns the
     * parsed notification so the caller can respond 200. Throws on a bad
     * signature or non-JSON body (see Webhooks::parse).
     */
    public function dispatch(string $rawBody, ?string $signatureHeader = null): WebhookNotification
    {
        $hook = Webhooks::parse($rawBody, $signatureHeader, $this->secret);

        foreach ($this->matching($hook->event) as $handler) {
            $handler($hook);
        }

        return $hook;
    }

    /**
     * @return list<callable(WebhookNotification): mixed>
     */
    private function matching(string $event): array
    {
        $family = explode('.', $event)[0] ?? '';

        return array_merge(
            $this->handlers[$event] ?? [],
            $family !== '' ? ($this->handlers[$family.'.*'] ?? []) : [],
            $this->handlers['*'] ?? [],
        );
    }
}
