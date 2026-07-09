<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A reusable email template as returned by `/api/v1/email-templates`.
 *
 * `variables` is the declared placeholder schema (may be null). Unknown
 * fields fall through into `extra`.
 */
final class EmailTemplate
{
    /**
     * @param  array<string, mixed>|null  $variables
     * @param  array<string, mixed>       $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $subject,
        public readonly ?array $variables,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'name', 'slug', 'subject', 'variables', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            variables: isset($data['variables']) && is_array($data['variables']) ? $data['variables'] : null,
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
            'slug' => $this->slug,
            'subject' => $this->subject,
            'variables' => $this->variables,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
