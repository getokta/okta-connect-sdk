<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\DTO\PaginatedResult;
use Okta\Connect\WhatsApp\DTO\Workspace;
use Okta\Connect\WhatsApp\DTO\WorkspaceProvision;

/**
 * Workspaces you provision and manage.
 *
 * Scope is not a filter you pass — it is the boundary the API holds: every
 * lookup is pinned to the calling partner, and a workspace belonging to
 * someone else answers 404 rather than 403, so nothing leaks about tenants
 * you do not own.
 *
 * Abilities: `workspaces.read` for reads, `workspaces.write` for the rest.
 */
final class PartnerWorkspaces extends PartnerResource
{
    /**
     * Create a workspace — or match an existing one.
     *
     * Pass `external_id` (your identifier for the account) and the call
     * becomes idempotent: a repeat returns the same workspace with
     * `created: false` instead of a duplicate, which is what makes retrying
     * after a timeout safe.
     *
     * Optional `owner` (`name`, `email`, `password_auto`) creates and
     * installs the workspace owner in the same call; with `password_auto`
     * the generated password comes back on this response only.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?string $idempotencyKey = null): WorkspaceProvision
    {
        $response = $this->http->post(
            $this->partner('/workspaces'),
            $attributes,
            $this->idempotencyHeader($idempotencyKey),
        );

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        // 201 created, 200 matched an existing external_id. The body alone
        // cannot tell you which.
        return WorkspaceProvision::fromArray($payload, $response->statusCode() === 201);
    }

    /**
     * @param  array<string, mixed>  $filters  search, external_id, status, per_page (1–100)
     * @return PaginatedResult<Workspace>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get($this->partner('/workspaces'), $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): Workspace => Workspace::fromArray($item),
        );
    }

    public function get(string $ulid): Workspace
    {
        $response = $this->http->get($this->workspacePath($ulid));

        return Workspace::fromArray($this->unwrap($response->json()));
    }

    /**
     * Find the workspace you filed under this `external_id`, or null.
     *
     * The reconciliation primitive: it answers "have I provisioned this
     * account already" without creating anything as a side effect.
     */
    public function findByExternalId(string $externalId): ?Workspace
    {
        $matches = $this->list(['external_id' => $externalId, 'per_page' => 1]);

        foreach ($matches as $workspace) {
            return $workspace;
        }

        return null;
    }

    /**
     * `name`, `locale`, `timezone`, `country`, `metadata`. `slug` is
     * immutable once issued — integrations key their own records off it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $ulid, array $attributes): Workspace
    {
        $response = $this->http->patch($this->workspacePath($ulid), $attributes);

        return Workspace::fromArray($this->unwrap($response->json()));
    }

    /** Your kill switch for an account that churned inside your product. */
    public function suspend(string $ulid): Workspace
    {
        $response = $this->http->post($this->workspacePath($ulid, '/suspend'));

        return Workspace::fromArray($this->unwrap($response->json()));
    }

    public function activate(string $ulid): Workspace
    {
        $response = $this->http->post($this->workspacePath($ulid, '/activate'));

        return Workspace::fromArray($this->unwrap($response->json()));
    }
}
