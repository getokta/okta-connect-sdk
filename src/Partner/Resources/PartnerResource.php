<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Partner\Resources;

use Okta\Connect\WhatsApp\Resources\Resource;

/**
 * Shared base for the Partner API surface — everything under
 * `/api/v1/partner/*`.
 *
 * Separate from the tenant resources on purpose: partner tokens and tenant
 * tokens live in different stores and are rejected by each other's routes,
 * so mixing the two surfaces on one client would only ever produce 401s
 * that look like bugs.
 */
abstract class PartnerResource extends Resource
{
    /** Prefix a partner-relative path, e.g. partner('/workspaces'). */
    protected function partner(string $path): string
    {
        return $this->api('partner/'.ltrim($path, '/'));
    }

    /** Path to one workspace's sub-collection, with the ulid escaped. */
    protected function workspacePath(string $ulid, string $suffix = ''): string
    {
        return $this->partner('/workspaces/'.rawurlencode($ulid).$suffix);
    }
}
