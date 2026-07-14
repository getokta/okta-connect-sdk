<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

use Okta\Connect\WhatsApp\Enums\WebhookEvent;

/**
 * A parsed inbound webhook delivery.
 *
 * The platform POSTs this envelope to your endpoint:
 *
 *   { "event": "message.sent", "organization_id": 42,
 *     "payload": { ...event-specific... },
 *     "delivery_id": "01H…", "sent_at": "2026-07-14T…Z" }
 *
 * Parse + verify it in one call (see Webhooks::parse), then branch on type():
 *
 *   $hook = $client->webhooks()->parse($rawBody, $sigHeader, $secret);
 *   if ($hook->type() === WebhookEvent::MessageSent) {
 *       $hook->conversationId();  // which conversation
 *       $hook->channelType();     // which channel
 *       $hook->isReply();         // and whether it's a reply
 *   }
 */
final class WebhookNotification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $event,
        public readonly ?int $organizationId,
        public readonly array $payload,
        public readonly ?string $deliveryId,
        public readonly ?string $sentAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  The decoded top-level envelope.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            event: isset($data['event']) ? (string) $data['event'] : '',
            organizationId: isset($data['organization_id']) && is_numeric($data['organization_id'])
                ? (int) $data['organization_id']
                : null,
            payload: isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : [],
            deliveryId: isset($data['delivery_id']) ? (string) $data['delivery_id'] : null,
            sentAt: isset($data['sent_at']) ? (string) $data['sent_at'] : null,
        );
    }

    /** The typed event, or null for an event this SDK version doesn't know. */
    public function type(): ?WebhookEvent
    {
        return WebhookEvent::tryFrom($this->event);
    }

    public function is(WebhookEvent|string $event): bool
    {
        return $this->event === ($event instanceof WebhookEvent ? $event->value : $event);
    }

    /** True for any message.* event. */
    public function isMessageEvent(): bool
    {
        return str_starts_with($this->event, 'message.');
    }

    /**
     * Read a dotted path out of the event payload — e.g. get('message.body')
     * or get('channel.type').
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->payload;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    // ---- message.* convenience accessors (null when not applicable) ---------

    public function conversationId(): ?string
    {
        $id = $this->get('conversation.id');

        return is_string($id) ? $id : null;
    }

    public function channelType(): ?string
    {
        $type = $this->get('channel.type');

        return is_string($type) ? $type : null;
    }

    public function messageBody(): ?string
    {
        $body = $this->get('message.body');

        return is_string($body) ? $body : null;
    }

    public function isReply(): bool
    {
        return $this->get('message.is_reply') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'event' => $this->event !== '' ? $this->event : null,
            'organization_id' => $this->organizationId,
            'payload' => $this->payload !== [] ? $this->payload : null,
            'delivery_id' => $this->deliveryId,
            'sent_at' => $this->sentAt,
        ], static fn ($v): bool => $v !== null);
    }
}
