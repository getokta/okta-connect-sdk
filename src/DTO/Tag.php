<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

final class Tag
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $color,
        public readonly ?string $scope,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'name', 'slug', 'color', 'scope', 'created_at'];

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            color: isset($data['color']) ? (string) $data['color'] : null,
            scope: isset($data['scope']) ? (string) $data['scope'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            extra: array_diff_key($data, array_flip($known)),
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
            'color' => $this->color,
            'scope' => $this->scope,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
