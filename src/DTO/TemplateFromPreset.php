<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * What `POST /api/v1/templates/presets/{key}` returned: the new template plus
 * the outcome of the optional immediate submission (`meta.submission`).
 *
 * The template is created whether or not submission worked, so a failed
 * submission is not a failed call — check {@see self::submissionFailed()}
 * and retry with `templates()->submit()` once the cause is fixed. The usual
 * error is `no_template_capable_channel`: nothing in the workspace has a WABA.
 */
final readonly class TemplateFromPreset
{
    public function __construct(
        public Template $template,
        public bool $submissionAttempted,
        public bool $submitted,
        public ?string $submissionError,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  The whole response body (`data` + `meta`).
     */
    public static function fromArray(array $payload): self
    {
        /** @var array<string, mixed> $data */
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $submission = is_array($meta['submission'] ?? null) ? $meta['submission'] : null;
        $error = $submission['error'] ?? null;

        return new self(
            template: Template::fromArray($data),
            submissionAttempted: $submission !== null,
            submitted: $submission !== null && ($submission['submitted'] ?? false) === true,
            submissionError: is_string($error) && $error !== '' ? $error : null,
        );
    }

    /** Submission was requested and did not go through. */
    public function submissionFailed(): bool
    {
        return $this->submissionAttempted && ! $this->submitted;
    }
}
