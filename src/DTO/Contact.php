<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

final class Contact
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $phone,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['id', 'phone', 'name', 'email', 'created_at'];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
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
            'phone' => $this->phone,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
