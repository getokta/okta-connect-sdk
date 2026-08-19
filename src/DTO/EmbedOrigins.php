<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * The result of replacing the framing allowlist.
 *
 * `rejected` is the half worth reading. An origin that did not parse is
 * simply absent from the stored list, and the symptom lands in the browser
 * as a blank iframe with a 200 and no failed request — the one failure mode
 * devtools cannot explain. Compare what you sent against `rejected` at
 * deploy time instead.
 */
final class EmbedOrigins
{
    /**
     * @param  list<string>  $origins  What the platform stored.
     * @param  list<string>  $rejected  What it refused to store, verbatim.
     */
    public function __construct(
        public readonly array $origins,
        public readonly array $rejected = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

        $read = static function (mixed $raw): array {
            $out = [];

            if (is_array($raw)) {
                foreach ($raw as $value) {
                    $out[] = (string) $value;
                }
            }

            return $out;
        };

        return new self(
            origins: $read($payload['origins'] ?? null),
            rejected: $read($payload['rejected'] ?? null),
        );
    }

    public function hasRejections(): bool
    {
        return $this->rejected !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['origins' => $this->origins, 'rejected' => $this->rejected];
    }
}
