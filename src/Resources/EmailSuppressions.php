<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\EmailSuppression;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/emails/suppressions — the do-not-send list.
 *
 * Reached via the parent accessor: `$client->emails()->suppressions()`.
 * Addresses land here from hard bounces / complaints, and can be added
 * or removed manually.
 */
final class EmailSuppressions extends Resource
{
    /**
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<EmailSuppression>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/emails/suppressions', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): EmailSuppression => EmailSuppression::fromArray($item),
        );
    }

    /**
     * Add an address to the suppression list.
     */
    public function add(string $address, ?string $idempotencyKey = null): EmailSuppression
    {
        $response = $this->http->post(
            '/api/v1/emails/suppressions',
            ['address' => $address],
            $this->idempotencyHeader($idempotencyKey),
        );

        return EmailSuppression::fromArray($this->unwrap($response->json()));
    }

    /**
     * Remove a suppression by its ULID or raw address.
     */
    public function remove(string $idOrAddress): bool
    {
        $response = $this->http->delete('/api/v1/emails/suppressions/'.rawurlencode($idOrAddress));

        return ($response->json()['deleted'] ?? false) === true;
    }
}
