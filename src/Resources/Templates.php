<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\Message;
use Okta\Connect\WhatsApp\DTO\Template;
use Okta\Connect\WhatsApp\DTO\TemplateFromPreset;
use Okta\Connect\WhatsApp\DTO\TemplatePreset;

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
 *
 * Authoring — `save()`, `submit()`, `delete()` and `createFromPreset()` —
 * needs a `write` token, never `send`: a client can own the whole template
 * cycle without being allowed to message anyone.
 *
 *   // Start from the library instead of a blank page:
 *   $presets = $client->templates()->presets(['category' => 'utility', 'language' => 'ar']);
 *   $result  = $client->templates()->createFromPreset('order_shipped', ['group' => 'Orders', 'submit' => true]);
 *   if ($result->submissionFailed()) { ... $result->submissionError ... }
 */
final class Templates extends Resource
{
    /**
     * List templates. Filters: `status` (DRAFT, PENDING, APPROVED, REJECTED,
     * PAUSED, DISABLED), `language`, `category` (UTILITY / MARKETING /
     * AUTHENTICATION, any case), `group` (the merchant's folder) and
     * `preset_key` (only copies of one library preset).
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

    /**
     * Create or update a template, stored as a DRAFT (this does not submit it).
     *
     * Re-posting an existing (`name`, `language`) pair updates that template.
     * Body: `name`, `language`, `body_text` (required); `category`,
     * `channel_id`, `header_type`, `header_text`, `footer_text`,
     * `example_variables` (one per `{{N}}`), `group`, `buttons`. Omit
     * `group` to keep the current folder; pass null or '' to clear it.
     *
     * `buttons`: up to 3 of `['type' => 'QUICK_REPLY'|'URL'|'PHONE_NUMBER',
     * 'text' => ≤25 chars]`, plus `url` for URL and `phone_number` for
     * PHONE_NUMBER. Same rule as `group`: omit the key to keep the buttons
     * already stored (e.g. added in the dashboard); pass `[]` or null to
     * remove them. The payload is sent as given, so an absent key stays absent.
     *
     * @param  array{
     *     name: string,
     *     language: string,
     *     body_text: string,
     *     category?: string,
     *     channel_id?: string|null,
     *     header_type?: string|null,
     *     header_text?: string|null,
     *     footer_text?: string|null,
     *     example_variables?: list<string>|null,
     *     group?: string|null,
     *     buttons?: list<array{type: 'QUICK_REPLY'|'URL'|'PHONE_NUMBER', text: string, url?: string, phone_number?: string}>|null,
     *     ...
     * }  $payload
     */
    public function save(array $payload): Template
    {
        $response = $this->http->post($this->api('/templates'), $payload);

        return Template::fromArray($this->unwrap((array) $response->json()));
    }

    /**
     * Submit a template to Meta for review. `$channelId` is only needed when
     * the template has no channel of its own; the platform otherwise picks
     * the template's channel, or the workspace's only template-capable one.
     * A 422 `no_template_capable_channel` means nothing in the workspace has
     * a WABA (see `Channel::$supportsTemplates`).
     */
    public function submit(string $ulid, ?string $channelId = null): Template
    {
        $response = $this->http->post(
            $this->api('/templates/'.rawurlencode($ulid).'/submit'),
            $channelId === null ? [] : ['channel_id' => $channelId],
        );

        return Template::fromArray($this->unwrap((array) $response->json()));
    }

    /** Delete a template — from Meta too when it was submitted. */
    public function delete(string $ulid): bool
    {
        $response = $this->http->delete($this->api('/templates/'.rawurlencode($ulid)));

        return ($response->json()['deleted'] ?? false) === true;
    }

    /**
     * The library of ready-made templates. Filters: `category` (utility /
     * marketing / authentication), `group` (general, commerce, support…),
     * `language` (also narrows each preset's `variants` to that language).
     *
     * @param  array<string, mixed>  $filters
     * @return list<TemplatePreset>
     */
    public function presets(array $filters = []): array
    {
        $response = $this->http->get($this->api('/templates/presets'), $filters);
        $rows = (array) ($response->json()['data'] ?? []);

        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $out[] = TemplatePreset::fromArray($row);
            }
        }

        return $out;
    }

    /**
     * Add a library preset to the workspace as a DRAFT template.
     *
     * Options: `language` (defaults to the preset's first, Arabic),
     * `channel_id` (channel ULID owning the WABA), `name` (override — one
     * already taken is refused with 422, whereas the default name is
     * suffixed `_2`, `_3`…), `group` (folder) and `submit` (send to Meta
     * straight away). The template is created even when submission fails;
     * the result says why. 404 `preset_not_found` / `channel_not_found`.
     *
     * @param  array{language?: string, channel_id?: string, name?: string, group?: string, submit?: bool}  $options
     */
    public function createFromPreset(string $key, array $options = []): TemplateFromPreset
    {
        $response = $this->http->post(
            $this->api('/templates/presets/'.rawurlencode($key)),
            $options,
        );

        /** @var array<string, mixed> $payload */
        $payload = (array) $response->json();

        return TemplateFromPreset::fromArray($payload);
    }
}
