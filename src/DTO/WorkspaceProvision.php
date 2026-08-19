<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * What `POST /workspaces` returns, including the part a plain Workspace
 * cannot express: whether this call created the workspace or matched an
 * existing one by `external_id`.
 *
 * That distinction is the whole point of the idempotency contract. A retry
 * after a timeout answers `created: false` and hands back the same
 * workspace — so a provisioning job can be re-run without minting duplicate
 * accounts, and without your code having to guess from the absence of an
 * owner block.
 */
final class WorkspaceProvision
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly bool $created,
        public readonly ?WorkspaceUser $owner = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  bool  $created  From the HTTP status: 201 created, 200 matched.
     */
    public static function fromArray(array $payload, bool $created): self
    {
        $owner = null;

        if (isset($payload['owner']) && is_array($payload['owner'])) {
            $owner = WorkspaceUser::fromArray($payload['owner']);
        }

        return new self(
            workspace: Workspace::fromArray($payload),
            created: $created,
            owner: $owner,
        );
    }

    /**
     * The password to show the operator exactly once, when one was generated.
     */
    public function oneTimePassword(): ?string
    {
        return $this->owner?->oneTimePassword;
    }
}
