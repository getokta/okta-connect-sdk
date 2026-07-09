<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\DTO;

/**
 * A transactional email message as returned by the API.
 *
 * Field naming mirrors the JSON response. Optional fields are nullable;
 * unknown fields fall through into `extra` so the SDK doesn't strip data
 * the server may add later.
 */
final class EmailMessage
{
    /**
     * @param  array<int, mixed>|null    $to
     * @param  array<int, mixed>|null    $cc
     * @param  array<int, mixed>|null    $bcc
     * @param  array<string, mixed>|null $error
     * @param  array<string, mixed>      $extra
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $from,
        public readonly ?string $fromAddress,
        public readonly ?array $to,
        public readonly ?array $cc,
        public readonly ?array $bcc,
        public readonly ?string $replyTo,
        public readonly ?string $subject,
        public readonly ?string $status,
        public readonly ?string $messageId,
        public readonly ?string $providerMessageId,
        public readonly ?array $error,
        public readonly ?string $sentAt,
        public readonly ?string $deliveredAt,
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
            'id', 'from', 'from_address', 'to', 'cc', 'bcc', 'reply_to', 'subject',
            'status', 'message_id', 'provider_message_id', 'error', 'sent_at',
            'delivered_at', 'created_at',
        ];
        $extra = array_diff_key($data, array_flip($known));

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            from: isset($data['from']) ? (string) $data['from'] : null,
            fromAddress: isset($data['from_address']) ? (string) $data['from_address'] : null,
            to: isset($data['to']) && is_array($data['to']) ? $data['to'] : null,
            cc: isset($data['cc']) && is_array($data['cc']) ? $data['cc'] : null,
            bcc: isset($data['bcc']) && is_array($data['bcc']) ? $data['bcc'] : null,
            replyTo: isset($data['reply_to']) ? (string) $data['reply_to'] : null,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            messageId: isset($data['message_id']) ? (string) $data['message_id'] : null,
            providerMessageId: isset($data['provider_message_id']) ? (string) $data['provider_message_id'] : null,
            error: isset($data['error']) && is_array($data['error']) ? $data['error'] : null,
            sentAt: isset($data['sent_at']) ? (string) $data['sent_at'] : null,
            deliveredAt: isset($data['delivered_at']) ? (string) $data['delivered_at'] : null,
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
            'from' => $this->from,
            'from_address' => $this->fromAddress,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->replyTo,
            'subject' => $this->subject,
            'status' => $this->status,
            'message_id' => $this->messageId,
            'provider_message_id' => $this->providerMessageId,
            'error' => $this->error,
            'sent_at' => $this->sentAt,
            'delivered_at' => $this->deliveredAt,
            'created_at' => $this->createdAt,
        ], static fn ($v): bool => $v !== null) + $this->extra;
    }
}
