<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * Email deliverability analytics for a date window.
 *
 * `summary` carries the aggregate assoc array
 * (total/queued/sent/delivered/bounced/complained/failed/delivery_rate/
 * bounce_rate) and `series` a per-day breakdown. Trivial typed getters
 * expose the headline rates without reaching into the raw arrays.
 */
final class EmailAnalytics
{
    /**
     * @param  array<string, mixed>        $summary
     * @param  list<array<string, mixed>>  $series
     */
    public function __construct(
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly array $summary = [],
        public readonly array $series = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<array<string, mixed>> $series */
        $series = [];

        if (isset($data['series']) && is_array($data['series'])) {
            foreach ($data['series'] as $point) {
                if (is_array($point)) {
                    $series[] = $point;
                }
            }
        }

        return new self(
            from: isset($data['from']) ? (string) $data['from'] : null,
            to: isset($data['to']) ? (string) $data['to'] : null,
            summary: isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [],
            series: $series,
        );
    }

    public function total(): ?int
    {
        return isset($this->summary['total']) ? (int) $this->summary['total'] : null;
    }

    public function deliveryRate(): ?float
    {
        return isset($this->summary['delivery_rate']) ? (float) $this->summary['delivery_rate'] : null;
    }

    public function bounceRate(): ?float
    {
        return isset($this->summary['bounce_rate']) ? (float) $this->summary['bounce_rate'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'summary' => $this->summary,
            'series' => $this->series,
        ], static fn ($v): bool => $v !== null && $v !== []);
    }
}
