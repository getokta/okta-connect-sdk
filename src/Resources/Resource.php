<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources;

use Okta\WhatsApp\Http\HttpClientInterface;

/**
 * Shared scaffolding for resource clients.
 *
 * A resource is a thin object holding (a) the transport and (b) a base
 * path. Helpers below centralise idempotency-key handling and the
 * "unwrap a single-record JSON response" pattern — the API consistently
 * wraps single-record responses under a `data` key.
 */
abstract class Resource
{
    public function __construct(
        protected readonly HttpClientInterface $http,
    ) {
    }

    /**
     * @return array<string, string>
     */
    protected function idempotencyHeader(?string $key): array
    {
        return $key === null || $key === '' ? [] : ['Idempotency-Key' => $key];
    }

    /**
     * Pull a single record out of either `{"data": {...}}` or a bare object.
     *
     * @param  array<string, mixed>|list<mixed>  $payload
     * @return array<string, mixed>
     */
    protected function unwrap(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            /** @var array<string, mixed> $data */
            $data = $payload['data'];

            return $data;
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
