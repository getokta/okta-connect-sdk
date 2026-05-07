<?php

declare(strict_types=1);

namespace Okta\WhatsApp\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Generic wrapper around the Laravel-style paginator response shape:
 *
 * {
 *   "data":  [ ...items ],
 *   "links": { "first": ..., "last": ..., "prev": ..., "next": ... },
 *   "meta":  { "current_page": 1, "per_page": 50, "total": 200, "next_cursor": "...", ... }
 * }
 *
 * @template TItem
 * @implements IteratorAggregate<int, TItem>
 */
final class PaginatedResult implements Countable, IteratorAggregate
{
    /**
     * @param  list<TItem>             $items
     * @param  array<string, mixed>    $links
     * @param  array<string, mixed>    $meta
     */
    public function __construct(
        private readonly array $items,
        private readonly array $links = [],
        private readonly array $meta = [],
    ) {
    }

    /**
     * Build from a raw decoded JSON response and a per-item DTO factory.
     *
     * @template TOut
     * @param  array<string, mixed>            $payload
     * @param  callable(array<string, mixed>): TOut  $factory
     * @return self<TOut>
     */
    public static function fromArray(array $payload, callable $factory): self
    {
        $items = [];
        $rawData = $payload['data'] ?? $payload;

        if (is_array($rawData)) {
            foreach ($rawData as $item) {
                if (is_array($item)) {
                    $items[] = $factory($item);
                }
            }
        }

        $links = isset($payload['links']) && is_array($payload['links']) ? $payload['links'] : [];
        $meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];

        return new self($items, $links, $meta);
    }

    /**
     * @return list<TItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function links(): array
    {
        return $this->links;
    }

    public function nextCursor(): ?string
    {
        if (isset($this->meta['next_cursor']) && is_string($this->meta['next_cursor'])) {
            return $this->meta['next_cursor'];
        }

        if (isset($this->links['next']) && is_string($this->links['next'])) {
            return $this->links['next'];
        }

        return null;
    }

    public function hasMore(): bool
    {
        if ($this->nextCursor() !== null) {
            return true;
        }

        if (isset($this->meta['current_page'], $this->meta['last_page'])
            && is_int($this->meta['current_page']) && is_int($this->meta['last_page'])) {
            return $this->meta['current_page'] < $this->meta['last_page'];
        }

        return false;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
