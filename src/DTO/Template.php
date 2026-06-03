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
 */
final readonly class Template
{
    /**
     * @param  list<mixed>|null      $buttons
     * @param  array<string, mixed>  $extra
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
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'id', 'name', 'language', 'category', 'status',
            'header_type', 'header_text', 'body_text', 'footer_text',
            'buttons', 'created_at',
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
            buttons: isset($data['buttons']) && is_array($data['buttons']) ? array_values($data['buttons']) : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: $extra,
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
            'header_type' => $this->headerType,
            'header_text' => $this->headerText,
            'body_text' => $this->bodyText,
            'footer_text' => $this->footerText,
            'buttons' => $this->buttons,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
