<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Member;
use Okta\Connect\WhatsApp\DTO\MemberResult;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/members — the people of the workspace, managed by your system.
 *
 * When your product adds an employee, add them here too with the id you
 * know them by. From then on, pass that id as the `sender` of a message
 * (`'ext:'.$yourId`) and it is recorded — in the inbox, in the API and in
 * webhooks — under their name instead of under the token's owner.
 *
 * Reading needs any ability; changing needs `members` (or `admin`).
 * Members added this way cannot sign in on their own.
 *
 * A member is referenced by Connect's id, by `ext:<your id>`, or by e-mail.
 */
final class Members extends Resource
{
    /**
     * @param  array<string, mixed>  $filters  e.g. ['external_id' => 'emp-41', 'per_page' => 100]
     * @return PaginatedResult<Member>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get($this->api('/members'), $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Member => Member::fromArray($item),
        );
    }

    /**
     * Create the member, or update the one already there.
     *
     * Idempotent on `external_id`, then on `email` — call it every time the
     * person changes on your side. Only the fields you pass are touched: a
     * repeat call never undoes a role set by hand in the dashboard, never
     * re-activates someone who was suspended.
     *
     * @param  array{external_id?: string, email?: string, name?: string, role?: string, phone?: string, locale?: string}  $member
     *                                                                                                                              `external_id` or `email` is required. `role`: manager | agent (default) | member.
     */
    public function upsert(array $member): MemberResult
    {
        $response = $this->http->put($this->api('/members'), $member);

        return new MemberResult(
            member: Member::fromArray($this->unwrap($response->json())),
            // 201 created them, 200 found them.
            created: $response->statusCode() === 201,
        );
    }

    public function get(string $reference): Member
    {
        $response = $this->http->get($this->path($reference));

        return Member::fromArray($this->unwrap($response->json()));
    }

    /**
     * @param  array{name?: string, role?: string, status?: string}  $changes  `status`: active | suspended. A
     *                                                                         suspended member cannot be a `sender`.
     */
    public function update(string $reference, array $changes): Member
    {
        $response = $this->http->patch($this->path($reference), $changes);

        return Member::fromArray($this->unwrap($response->json()));
    }

    /**
     * End the membership. Messages they sent keep their name.
     */
    public function remove(string $reference): bool
    {
        $response = $this->http->delete($this->path($reference));

        return $response->statusCode() >= 200 && $response->statusCode() < 300;
    }

    /** The reference for a member you know by your own id. */
    public static function external(string $externalId): string
    {
        return 'ext:'.$externalId;
    }

    private function path(string $reference): string
    {
        // Slashes stay: an external id may contain them, and the route takes the rest of the path.
        return $this->api('/members/'.str_replace('%2F', '/', rawurlencode($reference)));
    }
}
