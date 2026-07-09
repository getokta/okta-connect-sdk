<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\EmailAnalytics;
use Okta\Connect\WhatsApp\DTO\EmailMessage;
use Okta\Connect\WhatsApp\DTO\PaginatedResult;

/**
 * /api/v1/emails — transactional email send + send log + deliverability
 * analytics, with nested accessors for templates, broadcasts and the
 * suppression list.
 *
 *   $client->emails()->send([...]);
 *   $client->emails()->sendTemplate('Acme <no-reply@acme.com>', ['a@x.com'], 'welcome', ['name' => 'Ali']);
 *   $client->emails()->templates()->create([...]);
 *   $client->emails()->broadcasts()->queue($ulid);
 *   $client->emails()->suppressions()->add('bounced@example.com');
 */
final class Emails extends Resource
{
    private ?EmailTemplates $templates = null;

    private ?EmailBroadcasts $broadcasts = null;

    private ?EmailSuppressions $suppressions = null;

    /**
     * Send an email. Body: `from` ("Name <addr@d>" or bare address),
     * `to` (string[]), and at least one of `html` / `text` / `template`;
     * optional `subject`, `variables`, `cc`, `bcc`, `reply_to`, `headers`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload, ?string $idempotencyKey = null): EmailMessage
    {
        $response = $this->http->post(
            '/api/v1/emails',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return EmailMessage::fromArray($this->unwrap($response->json()));
    }

    /**
     * Convenience: send a stored template to one or more recipients,
     * merging `$overrides` (which may add cc/bcc/reply_to/subject).
     *
     * @param  list<string>          $to
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>  $overrides
     */
    public function sendTemplate(
        string $from,
        array $to,
        string $template,
        array $variables = [],
        array $overrides = [],
        ?string $idempotencyKey = null,
    ): EmailMessage {
        return $this->send([
            'from' => $from,
            'to' => $to,
            'template' => $template,
            'variables' => $variables,
        ] + $overrides, $idempotencyKey);
    }

    /**
     * List sent emails. Filters (e.g. `status`, `per_page`) pass through
     * as the query string.
     *
     * @param  array<string, mixed>  $filters
     * @return PaginatedResult<EmailMessage>
     */
    public function list(array $filters = []): PaginatedResult
    {
        $response = $this->http->get('/api/v1/emails', $filters);

        return PaginatedResult::fromArray(
            $response->json(),
            static fn (array $item): EmailMessage => EmailMessage::fromArray($item),
        );
    }

    public function get(string $ulid): EmailMessage
    {
        $response = $this->http->get('/api/v1/emails/'.rawurlencode($ulid));

        return EmailMessage::fromArray($this->unwrap($response->json()));
    }

    /**
     * Deliverability analytics for a date window (`YYYY-MM-DD`). Both
     * bounds are optional and only sent when non-null.
     */
    public function analytics(?string $from = null, ?string $to = null): EmailAnalytics
    {
        $query = array_filter([
            'from' => $from,
            'to' => $to,
        ], static fn ($v): bool => $v !== null);

        $response = $this->http->get('/api/v1/emails/analytics', $query);

        return EmailAnalytics::fromArray($this->unwrap($response->json()));
    }

    public function templates(): EmailTemplates
    {
        return $this->templates ??= new EmailTemplates($this->http);
    }

    public function broadcasts(): EmailBroadcasts
    {
        return $this->broadcasts ??= new EmailBroadcasts($this->http);
    }

    public function suppressions(): EmailSuppressions
    {
        return $this->suppressions ??= new EmailSuppressions($this->http);
    }
}
