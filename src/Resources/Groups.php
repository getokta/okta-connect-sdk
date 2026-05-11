<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Resources;

use Okta\Connect\WhatsApp\DTO\WhatsAppGroup;

/**
 * WhatsApp groups — Baileys-only.
 *
 *   $g = $client->groups()->create('Sales pod', ['966500000000', '966500000001']);
 *   $client->groups()->addParticipants($g->id, ['966500000002']);
 *   $client->groups()->update($g->id, subject: 'Renamed pod');
 *   $client->groups()->remove($g->id, ['966500000000']);
 *   $client->groups()->setPicture($g->id, base64_encode($jpegBytes));
 *   $client->groups()->sync($g->id);
 *
 * Every mutating call round-trips the platform → gateway, so the
 * returned WhatsAppGroup reflects ground truth from WhatsApp at that
 * moment. Listings stay light; call `get($id)` for participants.
 */
final class Groups extends Resource
{
    /**
     * @return list<WhatsAppGroup>
     */
    public function list(): array
    {
        $response = $this->http->get('/api/v1/groups');
        $rows = (array) ($response->json()['data'] ?? []);

        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = WhatsAppGroup::fromArray($row);
            }
        }

        return $out;
    }

    public function get(string $id): WhatsAppGroup
    {
        $response = $this->http->get('/api/v1/groups/'.rawurlencode($id));

        return WhatsAppGroup::fromArray($this->unwrap((array) $response->json()));
    }

    /**
     * @param  list<string>  $participants  phone numbers (digits only)
     */
    public function create(string $subject, array $participants, ?int $channelId = null, ?string $idempotencyKey = null): WhatsAppGroup
    {
        $body = ['subject' => $subject, 'participants' => $participants];

        if ($channelId !== null) {
            $body['channel_id'] = $channelId;
        }

        $response = $this->http->post('/api/v1/groups', $body, $this->idempotencyHeader($idempotencyKey));

        return WhatsAppGroup::fromArray($this->unwrap((array) $response->json()));
    }

    public function update(string $id, ?string $subject = null, ?string $description = null): WhatsAppGroup
    {
        $body = array_filter([
            'subject' => $subject,
            'description' => $description,
        ], static fn ($v) => $v !== null);

        $response = $this->http->patch('/api/v1/groups/'.rawurlencode($id), $body);

        return WhatsAppGroup::fromArray($this->unwrap((array) $response->json()));
    }

    /**
     * @param  list<string>  $participants
     * @return list<array{jid: string, status: string}>
     */
    public function addParticipants(string $id, array $participants): array
    {
        $response = $this->http->post(
            '/api/v1/groups/'.rawurlencode($id).'/participants',
            ['participants' => $participants],
        );

        $results = (array) ($response->json()['data']['results'] ?? []);

        return $this->normaliseParticipantResults($results);
    }

    /**
     * @param  list<string>  $participants
     * @return list<array{jid: string, status: string}>
     */
    public function removeParticipants(string $id, array $participants): array
    {
        $response = $this->http->delete(
            '/api/v1/groups/'.rawurlencode($id).'/participants',
            ['participants' => $participants],
        );

        $results = (array) ($response->json()['data']['results'] ?? []);

        return $this->normaliseParticipantResults($results);
    }

    public function setPicture(string $id, string $base64Image): WhatsAppGroup
    {
        $response = $this->http->put(
            '/api/v1/groups/'.rawurlencode($id).'/picture',
            ['picture_base64' => $base64Image],
        );

        return WhatsAppGroup::fromArray($this->unwrap((array) $response->json()));
    }

    /**
     * Force a participants refresh from the gateway. Useful after an
     * external change (member added their phone, owner removed someone
     * via the WhatsApp app, …) when our local roster has drifted.
     */
    public function sync(string $id): WhatsAppGroup
    {
        $response = $this->http->post('/api/v1/groups/'.rawurlencode($id).'/sync', []);

        return WhatsAppGroup::fromArray($this->unwrap((array) $response->json()));
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{jid: string, status: string}>
     */
    private function normaliseParticipantResults(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (is_array($row) && isset($row['jid']) && is_string($row['jid'])) {
                $out[] = [
                    'jid' => $row['jid'],
                    'status' => is_string($row['status'] ?? null) ? $row['status'] : '',
                ];
            }
        }

        return $out;
    }
}
