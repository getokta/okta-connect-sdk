<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Resources;

use Okta\WhatsApp\DTO\Contact;
use Okta\WhatsApp\DTO\PaginatedResult;

final class Contacts extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<Contact>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/contacts', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Contact => Contact::fromArray($item),
        );
    }

    public function get(string $id): Contact
    {
        $response = $this->http->get('/api/v1/contacts/'.rawurlencode($id));

        return Contact::fromArray($this->unwrap($response->json()));
    }

    /**
     * Upsert a contact. The server treats POST /api/v1/contacts as
     * create-or-update keyed by `phone` within the caller's tenant.
     *
     * @param  array<string, mixed>  $payload
     */
    public function upsert(array $payload, ?string $idempotencyKey = null): Contact
    {
        $response = $this->http->post(
            '/api/v1/contacts',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Contact::fromArray($this->unwrap($response->json()));
    }
}
