<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Http;

/**
 * Minimal contract every transport must satisfy.
 *
 * The default implementation is Guzzle-backed but anything that can
 * round-trip the four shapes below works — useful for tests and for
 * wiring an alternative PSR-18 client.
 */
interface HttpClientInterface
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = [], array $headers = []): Response;

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(string $path, array $body = [], array $headers = []): Response;

    /**
     * @param  array<string, mixed>  $body
     */
    public function patch(string $path, array $body = [], array $headers = []): Response;

    /**
     * @param  array<string, mixed>  $body
     */
    public function put(string $path, array $body = [], array $headers = []): Response;

    public function delete(string $path, array $headers = []): Response;
}
