<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * What adding a member returned, including the part the body cannot say:
 * whether Connect created the account or reused one that already existed.
 *
 * The distinction matters exactly once — deciding whether there is a
 * one-time password to show. A reused account keeps its own password and
 * returns none, and treating that as a failure is the usual way this call
 * gets mis-integrated.
 */
final class MembershipResult
{
    public function __construct(
        public readonly WorkspaceUser $user,
        public readonly bool $created,
    ) {}

    /** The generated password, when one was generated. Shown once, never again. */
    public function oneTimePassword(): ?string
    {
        return $this->user->oneTimePassword;
    }
}
