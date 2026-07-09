<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\EmailTemplate;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/email-templates — reusable email templates.
 *
 * Reached via the parent accessor: `$client->emails()->templates()`.
 */
final class EmailTemplates extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<EmailTemplate>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/email-templates', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): EmailTemplate => EmailTemplate::fromArray($item),
        );
    }

    public function get(string $ulid): EmailTemplate
    {
        $response = $this->http->get('/api/v1/email-templates/'.rawurlencode($ulid));

        return EmailTemplate::fromArray($this->unwrap($response->json()));
    }

    /**
     * Create a template. Body: `name` (required), optional `slug`,
     * `subject`, `html`, `text`, `variables`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): EmailTemplate
    {
        $response = $this->http->post(
            '/api/v1/email-templates',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return EmailTemplate::fromArray($this->unwrap($response->json()));
    }

    /**
     * Update a template. Body: any of `name`, `slug`, `subject`, `html`,
     * `text`, `variables`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function update(string $ulid, array $payload): EmailTemplate
    {
        $response = $this->http->patch('/api/v1/email-templates/'.rawurlencode($ulid), $payload);

        return EmailTemplate::fromArray($this->unwrap($response->json()));
    }

    public function delete(string $ulid): bool
    {
        $response = $this->http->delete('/api/v1/email-templates/'.rawurlencode($ulid));

        return ($response->json()['deleted'] ?? false) === true;
    }
}
