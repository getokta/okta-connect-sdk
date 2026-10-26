<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A Meta message template as returned by `GET /api/v1/templates`.
 *
 * Field naming mirrors the JSON response. `status` carries the Meta
 * review state (DRAFT, PENDING, APPROVED, REJECTED, PAUSED, DISABLED);
 * unknown fields fall through into `extra` so the SDK never strips data
 * the platform may add later.
 *
 * `channelId` is the channel the template was authored against — submit it
 * through THAT channel's WABA. `group` is the merchant's own folder and
 * `presetKey` the library preset it was created from (null = written by
 * hand). `exampleVariables` holds one sample per `{{N}}`; send them back
 * with any edit, since Meta refuses a template without them.
 *
 * `buttons` is null when the template has none, otherwise up to three
 * `{type, text, url?, phone_number?}` rows (`type` QUICK_REPLY, URL or
 * PHONE_NUMBER). Keys the platform adds to a button are kept as-is.
 *
 * @phpstan-type TemplateButton array{type: string, text: string, url?: string, phone_number?: string, ...}
 */
final readonly class Template
{
    /**
     * @param  list<TemplateButton>|null  $buttons
     * @param  array<string, mixed>  $extra
     * @param  list<string>|null  $exampleVariables
     */
    public function __construct(
        public ?string $id,
        public ?string $name,
        public ?string $language,
        public ?string $category,
        public ?string $status,
        public ?string $headerType,
        public ?string $headerText,
        public ?string $bodyText,
        public ?string $footerText,
        public ?array $buttons,
        public ?string $createdAt,
        public array $extra = [],
        public ?string $channelId = null,
        public ?string $group = null,
        public ?string $presetKey = null,
        public ?array $exampleVariables = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'id', 'name', 'language', 'category', 'status',
            'header_type', 'header_text', 'body_text', 'footer_text',
            'buttons', 'created_at', 'channel_id', 'group', 'preset_key',
            'example_variables',
        ];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            language: isset($data['language']) ? (string) $data['language'] : null,
            category: isset($data['category']) ? (string) $data['category'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            headerType: isset($data['header_type']) ? (string) $data['header_type'] : null,
            headerText: isset($data['header_text']) ? (string) $data['header_text'] : null,
            bodyText: isset($data['body_text']) ? (string) $data['body_text'] : null,
            footerText: isset($data['footer_text']) ? (string) $data['footer_text'] : null,
            buttons: isset($data['buttons']) && is_array($data['buttons']) ? self::buttons($data['buttons']) : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: $extra,
            channelId: isset($data['channel_id']) ? (string) $data['channel_id'] : null,
            group: isset($data['group']) && $data['group'] !== '' ? (string) $data['group'] : null,
            presetKey: isset($data['preset_key']) ? (string) $data['preset_key'] : null,
            exampleVariables: isset($data['example_variables']) && is_array($data['example_variables'])
                ? array_values(array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $data['example_variables']))
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'language' => $this->language,
            'category' => $this->category,
            'status' => $this->status,
            'group' => $this->group,
            'preset_key' => $this->presetKey,
            'channel_id' => $this->channelId,
            'header_type' => $this->headerType,
            'header_text' => $this->headerText,
            'body_text' => $this->bodyText,
            'footer_text' => $this->footerText,
            'buttons' => $this->buttons,
            'example_variables' => $this->exampleVariables,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<TemplateButton>
     */
    private static function buttons(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $button = [
                'type' => is_scalar($row['type'] ?? null) ? (string) $row['type'] : '',
                'text' => is_scalar($row['text'] ?? null) ? (string) $row['text'] : '',
            ];
            if (is_scalar($row['url'] ?? null)) {
                $button['url'] = (string) $row['url'];
            }
            if (is_scalar($row['phone_number'] ?? null)) {
                $button['phone_number'] = (string) $row['phone_number'];
            }
            $out[] = $button + $row;
        }

        return $out;
    }

    /** Whether the template came from the library rather than being written by hand. */
    public function isFromPreset(): bool
    {
        return $this->presetKey !== null;
    }
}
