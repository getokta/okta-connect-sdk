<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Message;
use Okta\Connect\WhatsApp\DTO\Template;

/**
 * /api/v1/templates — Meta message templates.
 *
 *   $approved = $client->templates()->list(['status' => 'APPROVED']);
 *   $msg = $client->templates()->send([
 *       'channel_id'    => '01HZK7...',
 *       'wa_id'         => '966500000000',
 *       'template_name' => 'order_ready',
 *       'language'      => 'ar',
 *       'variables'     => ['12345', '120 SAR'],
 *   ]);
 *
 * `list()` returns a flat collection (the platform does not paginate the
 * template catalogue). `send()` queues a template message and returns the
 * resulting Message — `read`/`send` abilities respectively on the token.
 */
final class Templates extends Resource
{
    /**
     * List templates, optionally filtered by Meta `status` and/or `language`.
     *
     * @param  array<string, mixed>  $filters
     * @return list<Template>
     */
    public function list(array $filters = []): array
    {
        $response = $this->http->get('/api/v1/templates', $filters);
        $rows = (array) ($response->json()['data'] ?? []);

        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = Template::fromArray($row);
            }
        }

        return $out;
    }

    /**
     * Send a Meta-approved template to a wa_id.
     *
     * @param  array{channel_id: string, wa_id: string, template_name: string, language?: string, variables?: list<mixed>}  $payload
     */
    public function send(array $payload, ?string $idempotencyKey = null): Message
    {
        $response = $this->http->post(
            '/api/v1/templates/send',
            $payload,
            $this->idempotencyHeader($idempotencyKey),
        );

        return Message::fromArray($this->unwrap((array) $response->json()));
    }
}
