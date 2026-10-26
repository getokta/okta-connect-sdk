<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * One language version of a library preset — the exact wording that is sent
 * to Meta for review.
 *
 * Field names match {@see Template} (`header_type`, `body_text`,
 * `example_variables`…) so the code that previews a stored template can
 * preview a preset too. `variableCount` is the number of `{{N}}`
 * placeholders in the body.
 */
final readonly class TemplatePresetVariant
{
    /**
     * @param  list<mixed>  $buttons
     * @param  list<string>  $exampleVariables
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $language,
        public ?string $headerType,
        public ?string $headerText,
        public string $bodyText,
        public ?string $footerText,
        public array $buttons,
        public array $exampleVariables,
        public int $variableCount,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'language', 'header_type', 'header_text', 'body_text', 'footer_text',
            'buttons', 'example_variables', 'variable_count',
        ];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            language: (string) ($data['language'] ?? ''),
            headerType: isset($data['header_type']) ? (string) $data['header_type'] : null,
            headerText: isset($data['header_text']) ? (string) $data['header_text'] : null,
            bodyText: (string) ($data['body_text'] ?? ''),
            footerText: isset($data['footer_text']) ? (string) $data['footer_text'] : null,
            buttons: is_array($data['buttons'] ?? null) ? array_values($data['buttons']) : [],
            exampleVariables: is_array($data['example_variables'] ?? null)
                ? array_values(array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $data['example_variables']))
                : [],
            variableCount: (int) ($data['variable_count'] ?? 0),
            extra: $extra,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'language' => $this->language,
            'header_type' => $this->headerType,
            'header_text' => $this->headerText,
            'body_text' => $this->bodyText,
            'footer_text' => $this->footerText,
            'buttons' => $this->buttons,
            'example_variables' => $this->exampleVariables,
            'variable_count' => $this->variableCount,
        ] + $this->extra;
    }
}
