<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A ready-made template from the platform's library
 * (`GET /api/v1/templates/presets`), written to pass Meta's review rules.
 *
 * `key` is what `templates()->createFromPreset()` takes; `name` is the Meta
 * template name the copy is stored under (suffixed `_2`, `_3`… when the
 * workspace already holds it). `category` is lower-case (`utility`,
 * `marketing`, `authentication`) — operational notices are utility, anything
 * Meta treats as promotion is labelled marketing. `title`, `description` and
 * `groupLabel` are already translated into the caller's locale.
 *
 * `variants` carries the wording per language. When the list was filtered by
 * `language`, only that variant is present.
 */
final readonly class TemplatePreset
{
    /**
     * @param  list<string>  $languages
     * @param  list<TemplatePresetVariant>  $variants
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $key,
        public string $name,
        public ?string $group,
        public ?string $groupLabel,
        public ?string $category,
        public ?string $title,
        public ?string $description,
        public array $languages,
        public array $variants,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'key', 'name', 'group', 'group_label', 'category', 'title',
            'description', 'languages', 'variants',
        ];
        $extra = array_diff_key($data, array_flip($known));

        $variants = [];
        foreach ((array) ($data['variants'] ?? []) as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $variants[] = TemplatePresetVariant::fromArray($row);
            }
        }

        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            group: isset($data['group']) ? (string) $data['group'] : null,
            groupLabel: isset($data['group_label']) ? (string) $data['group_label'] : null,
            category: isset($data['category']) ? (string) $data['category'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            languages: is_array($data['languages'] ?? null)
                ? array_values(array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $data['languages']))
                : [],
            variants: $variants,
            extra: $extra,
        );
    }

    /** The variant for one language, or null when the preset has none. */
    public function variant(string $language): ?TemplatePresetVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->language === $language) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'group' => $this->group,
            'group_label' => $this->groupLabel,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'languages' => $this->languages,
            'variants' => array_map(static fn (TemplatePresetVariant $v): array => $v->toArray(), $this->variants),
        ] + $this->extra;
    }
}
