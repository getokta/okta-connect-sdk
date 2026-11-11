<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * What `members()->upsert()` returned: the member, and whether this call
 * created them or found them already there.
 */
final class MemberResult
{
    public function __construct(
        public readonly Member $member,
        public readonly bool $created,
    ) {}
}
