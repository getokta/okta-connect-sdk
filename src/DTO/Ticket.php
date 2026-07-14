<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

final class Ticket
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?int $number,
        public readonly ?string $subject,
        public readonly ?string $description,
        public readonly ?string $priority,
        public readonly ?string $status,
        public readonly ?string $stageId,
        public readonly ?string $stage,
        public readonly ?string $pipelineId,
        public readonly ?string $contactId,
        public readonly ?string $resolvedAt,
        public readonly ?string $closedAt,
        public readonly ?string $createdAt,
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $known = [
            'id', 'number', 'subject', 'description', 'priority', 'status',
            'stage_id', 'stage', 'pipeline_id', 'contact_id',
            'resolved_at', 'closed_at', 'created_at',
        ];

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            number: isset($data['number']) && is_numeric($data['number']) ? (int) $data['number'] : null,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            priority: isset($data['priority']) ? (string) $data['priority'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            stageId: isset($data['stage_id']) ? (string) $data['stage_id'] : null,
            stage: isset($data['stage']) ? (string) $data['stage'] : null,
            pipelineId: isset($data['pipeline_id']) ? (string) $data['pipeline_id'] : null,
            contactId: isset($data['contact_id']) ? (string) $data['contact_id'] : null,
            resolvedAt: isset($data['resolved_at']) ? (string) $data['resolved_at'] : null,
            closedAt: isset($data['closed_at']) ? (string) $data['closed_at'] : null,
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
            'number' => $this->number,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'stage_id' => $this->stageId,
            'stage' => $this->stage,
            'pipeline_id' => $this->pipelineId,
            'contact_id' => $this->contactId,
            'resolved_at' => $this->resolvedAt,
            'closed_at' => $this->closedAt,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
