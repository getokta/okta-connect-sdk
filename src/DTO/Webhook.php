<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

final class Webhook
{
    /**
     * @param  list<string>            $events
     * @param  array<string, mixed>    $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $url,
        public readonly array $events,
        public readonly ?bool $isActive,
        public readonly ?int $maxAttempts,
        public readonly ?string $secret,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'name', 'url', 'events', 'is_active', 'max_attempts', 'secret', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        $events = [];

        if (isset($data['events']) && is_array($data['events'])) {
            foreach ($data['events'] as $event) {
                $events[] = (string) $event;
            }
        }

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            events: $events,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            maxAttempts: isset($data['max_attempts']) ? (int) $data['max_attempts'] : null,
            // Present ONLY in the create response — store it now, it is never
            // returned again.
            secret: isset($data['secret']) ? (string) $data['secret'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: $extra,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'events' => $this->events !== [] ? $this->events : null,
            'is_active' => $this->isActive,
            'max_attempts' => $this->maxAttempts,
            'secret' => $this->secret,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
