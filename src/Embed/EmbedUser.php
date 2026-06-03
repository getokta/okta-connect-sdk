<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Embed;

use InvalidArgumentException;

/**
 * The operator an embed token is minted for.
 *
 * `sub` is the partner's stable identifier for the user (anything
 * unique on the partner side); `email` is what the platform keys its
 * own User row off (find-or-create). `name` is optional display text —
 * the platform falls back to the email when it's blank.
 *
 * Bundling these three into one value object means callers can't
 * accidentally transpose positional `email` / `name` arguments — a
 * recurring source of "logged in as the wrong account" bugs when each
 * platform hand-rolled the JWT payload.
 */
final readonly class EmbedUser
{
    public function __construct(
        public string $sub,
        public string $email,
        public string $name = '',
    ) {
        if ($sub === '' || $email === '') {
            throw new InvalidArgumentException('EmbedUser requires a non-empty sub and email.');
        }
    }
}
