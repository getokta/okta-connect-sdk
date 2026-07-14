<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Webhook;

/**
 * Base for the per-family typed views over a webhook payload. Subclasses expose
 * named accessors; everything else is reachable via get('dotted.path').
 */
abstract class WebhookEventData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload)
    {
    }

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

    protected function str(string $path): ?string
    {
        $value = $this->get($path);

        return is_string($value) ? $value : null;
    }

    protected function bool(string $path): bool
    {
        return $this->get($path) === true;
    }
}
