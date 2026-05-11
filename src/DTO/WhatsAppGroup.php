<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * WhatsApp group snapshot — what `/api/v1/groups/{ulid}` returns.
 *
 * `participants` is only populated on single-record show / sync calls;
 * list responses leave it empty to keep the wire light.
 */
final readonly class WhatsAppGroup
{
    /**
     * @param  list<WhatsAppGroupParticipant>  $participants
     */
    public function __construct(
        public string $id,
        public string $groupJid,
        public string $subject,
        public ?string $description,
        public ?string $ownerJid,
        public ?string $pictureUrl,
        public ?int $channelId,
        public ?string $joinedAt,
        public array $participants = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<WhatsAppGroupParticipant> $participants */
        $participants = [];

        if (isset($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $p) {
                if (is_array($p)) {
                    $participants[] = WhatsAppGroupParticipant::fromArray($p);
                }
            }
        }

        return new self(
            id: (string) ($data['id'] ?? ''),
            groupJid: (string) ($data['group_jid'] ?? ''),
            subject: (string) ($data['subject'] ?? ''),
            description: is_string($data['description'] ?? null) ? $data['description'] : null,
            ownerJid: is_string($data['owner_jid'] ?? null) ? $data['owner_jid'] : null,
            pictureUrl: is_string($data['picture_url'] ?? null) ? $data['picture_url'] : null,
            channelId: isset($data['channel_id']) ? (int) $data['channel_id'] : null,
            joinedAt: is_string($data['joined_at'] ?? null) ? $data['joined_at'] : null,
            participants: $participants,
        );
    }
}
