<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Channel;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

final class Channels extends Resource
{
    /**
     * List channels. Filters pass through as the query string; the API
     * accepts `type` (a channel type value — cloud_api / baileys /
     * telegram / instagram_dm / twitter / linkedin / tiktok / email — or
     * the family alias `whatsapp`, which covers cloud_api + baileys) and
     * `status` (connected / disconnected / pending / failed).
     *
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Channel>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/channels', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Channel => Channel::fromArray($item),
        );
    }

    /**
     * Convenience: list channels of one platform type, optionally
     * narrowed by connection status. `$extra` merges into the query
     * string (e.g. `per_page`).
     *
     * @param  array<string, mixed>  $extra
     * @return PaginatedResult<Channel>
     */
    public function listByType(string $type, ?string $status = null, array $extra = []): PaginatedResult
    {
        $filters = ['type' => $type] + $extra;

        if ($status !== null) {
            $filters['status'] = $status;
        }

        return $this->list($filters);
    }

    /**
     * Convenience: WhatsApp channels of either flavour — the `whatsapp`
     * family alias covers cloud_api + baileys.
     *
     * @return PaginatedResult<Channel>
     */
    public function whatsapp(?string $status = null): PaginatedResult
    {
        return $this->listByType('whatsapp', $status);
    }

    /**
     * Convenience: connected channels, optionally narrowed to one type.
     *
     * @return PaginatedResult<Channel>
     */
    public function connected(?string $type = null): PaginatedResult
    {
        return $type === null
            ? $this->list(['status' => 'connected'])
            : $this->listByType($type, 'connected');
    }

    /**
     * Convenience: disconnected channels, optionally narrowed to one type.
     *
     * @return PaginatedResult<Channel>
     */
    public function disconnected(?string $type = null): PaginatedResult
    {
        return $type === null
            ? $this->list(['status' => 'disconnected'])
            : $this->listByType($type, 'disconnected');
    }

    /**
     * Convenience: channels stuck "awaiting scan" — WhatsApp (baileys) links
     * whose QR was never scanned. Combine with delete() to prune stale ones.
     *
     * @return PaginatedResult<Channel>
     */
    public function awaitingScan(?string $type = null): PaginatedResult
    {
        return $type === null
            ? $this->list(['status' => 'awaiting_scan'])
            : $this->listByType($type, 'awaiting_scan');
    }

    public function get(string $id): Channel
    {
        $response = $this->http->get('/api/v1/channels/'.rawurlencode($id));

        return Channel::fromArray($this->unwrap($response->json()));
    }

    /**
     * Delete a channel. Disconnects it (stopping any live WhatsApp gateway
     * session), emits a `channel.deleted` webhook, then removes it — the
     * programmatic way to prune stale/awaiting-scan channels. Requires a token
     * with the `write` (or `admin`) ability. Returns true on success.
     */
    public function delete(string $id): bool
    {
        $response = $this->http->delete('/api/v1/channels/'.rawurlencode($id));

        return ($response->json()['deleted'] ?? false) === true;
    }
}
