<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Contact;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;
use Okta\Connect\WhatsApp\DTO\Tag;

/**
 * CRM tags — list the org's tags and apply tag slugs to a contact (unknown
 * slugs are lazily created). Requires `read` to list, `write` to apply.
 */
final class Tags extends Resource
{
    /**
     * @param  array<string, mixed>  $filters  e.g. ['scope' => 'contact']
     * @return PaginatedResult<Tag>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get($this->api('/tags'), $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Tag => Tag::fromArray($item),
        );
    }

    /**
     * Apply tag slugs to a contact (by ULID). Returns the updated contact with
     * its tags. New slugs are created on the fly.
     *
     * @param  list<string>  $slugs
     */
    public function applyToContact(string $contactId, array $slugs): Contact
    {
        $response = $this->http->post(
            $this->api('/contacts/'.rawurlencode($contactId).'/tags'),
            ['tags' => array_values($slugs)],
        );

        return Contact::fromArray($this->unwrap($response->json()));
    }
}
