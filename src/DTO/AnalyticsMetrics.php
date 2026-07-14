<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * The aggregate metric totals for a date range, from GET /analytics/metrics.
 * `metrics` maps a metric key (e.g. "messages.inbound", "social.followers")
 * to its summed value over [from, to].
 */
final class AnalyticsMetrics
{
    /**
     * @param  array<string, float|int>  $metrics
     */
    public function __construct(
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly ?string $platform,
        public readonly array $metrics,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  The `data` object of the response.
     */
    public static function fromArray(array $data): self
    {
        $metrics = [];

        if (isset($data['metrics']) && is_array($data['metrics'])) {
            foreach ($data['metrics'] as $key => $value) {
                if (is_numeric($value)) {
                    $metrics[(string) $key] = $value + 0;
                }
            }
        }

        return new self(
            from: isset($data['from']) ? (string) $data['from'] : null,
            to: isset($data['to']) ? (string) $data['to'] : null,
            platform: isset($data['platform']) && $data['platform'] !== null ? (string) $data['platform'] : null,
            metrics: $metrics,
        );
    }

    /** A single metric total, or the given default when the key is absent. */
    public function metric(string $key, float|int $default = 0): float|int
    {
        return $this->metrics[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'platform' => $this->platform,
            'metrics' => $this->metrics,
        ];
    }
}
