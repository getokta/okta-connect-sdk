<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Embed;

use InvalidArgumentException;

/**
 * Canonical `ui_hide` feature keys understood by the embedded inbox.
 *
 * The platform reads the signed `ui_hide` claim and strips the listed
 * controls from the inbox chrome. Because the platform silently ignores
 * keys it doesn't recognise, a typo on the partner side used to fail
 * open — the feature stayed visible and nobody noticed until a customer
 * complained. Minting through these constants (and `UiHide::validate()`,
 * which the Embed minter calls for you) turns that silent failure into a
 * loud `InvalidArgumentException` at mint time.
 */
final class UiHide
{
    /** AI suggestions, AI reply buttons, sentiment, "smart suggestion" (اقتراح ذكي). */
    public const AI = 'ai';

    /** Close / reopen pill in the conversation header. */
    public const CLOSE_CONVERSATION = 'close_conversation';

    /** Agent + auto-assign dropdown (تعيين موظف). */
    public const ASSIGN_AGENT = 'assign_agent';

    /** Contact-details right rail (name, phone, tags, AI summary, notes). */
    public const SIDEBAR = 'sidebar';

    /** Channel switcher in the conversation header. */
    public const CHANGE_CHANNEL = 'change_channel';

    /** Snooze control. */
    public const SNOOZE = 'snooze';

    /** "Sync history" action. */
    public const SYNC_HISTORY = 'sync_history';

    /** Knowledge-base panel / button. */
    public const KNOWLEDGE_BASE = 'knowledge_base';

    /** AI smart-summary block. */
    public const SMART_SUMMARY = 'smart_summary';

    /** Render the sidebar collapsed by default (without hiding it). */
    public const SIDEBAR_DEFAULT_CLOSED = 'sidebar_default_closed';

    /**
     * Every key the embedded inbox honours.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::AI,
            self::CLOSE_CONVERSATION,
            self::ASSIGN_AGENT,
            self::SIDEBAR,
            self::CHANGE_CHANNEL,
            self::SNOOZE,
            self::SYNC_HISTORY,
            self::KNOWLEDGE_BASE,
            self::SMART_SUMMARY,
            self::SIDEBAR_DEFAULT_CLOSED,
        ];
    }

    /**
     * Normalise + validate a list of ui_hide keys: trims blanks,
     * de-duplicates, and rejects anything not in `all()`.
     *
     * @param  list<string>  $keys
     * @return list<string>
     *
     * @throws InvalidArgumentException on an unknown key.
     */
    public static function validate(array $keys): array
    {
        $known = self::all();
        $clean = [];

        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }

            if (! in_array($key, $known, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown ui_hide key "%s". Allowed keys: %s.',
                    $key,
                    implode(', ', $known),
                ));
            }

            $clean[] = $key;
        }

        return array_values(array_unique($clean));
    }
}
