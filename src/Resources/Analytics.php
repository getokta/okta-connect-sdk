<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\AnalyticsMetrics;

/**
 * Read-only analytics — aggregate metric totals over a date range across
 * conversations + social. Requires `read`.
 */
final class Analytics extends Resource
{
    /**
     * Metric totals for a window. Optional filters: `from` / `to` (Y-m-d;
     * defaults to the last 30 days) and `platform` (e.g. 'whatsapp', 'x').
     *
     * @param  array<string, mixed>  $filters
     */
    public function metrics(array $filters = []): AnalyticsMetrics
    {
        $response = $this->http->get($this->api('/analytics/metrics'), $filters);

        return AnalyticsMetrics::fromArray($this->unwrap($response->json()));
    }
}
