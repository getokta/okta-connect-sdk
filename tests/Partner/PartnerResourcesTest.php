<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Partner;

use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The provisioning surface: workspaces, their people, their tokens, their
 * channels, and the two ways a partner puts someone inside the product.
 */
final class PartnerResourcesTest extends TestCase
{
    public function test_creating_a_workspace_reports_that_it_was_created_and_carries_the_owner_password(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(201, [
                'data' => [
                    'id' => 'ws_1',
                    'name' => 'Acme Support',
                    'slug' => 'acme-support',
                    'status' => 'active',
                    'external_id' => 'acct_8891',
                ],
                'owner' => [
                    'id' => 'usr_1',
                    'email' => 'sara@acme.test',
                    'created' => true,
                    'one_time_password' => 'hunter2-generated',
                ],
            ]),
        ], $history);

        $result = $partner->workspaces()->create([
            'name' => 'Acme Support',
            'external_id' => 'acct_8891',
            'owner' => ['name' => 'Sara', 'email' => 'sara@acme.test', 'password_auto' => true],
        ]);

        $this->assertTrue($result->created);
        $this->assertSame('ws_1', $result->workspace->id);
        $this->assertSame('acct_8891', $result->workspace->externalId);
        $this->assertSame('hunter2-generated', $result->oneTimePassword());
        $this->assertSame('/api/v1/partner/workspaces', $history[1]['request']->getUri()->getPath());
    }

    public function test_a_repeated_external_id_is_reported_as_matched_not_created(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            // 200, not 201 — the platform matched the external_id.
            ResponseFactory::json(200, ['data' => ['id' => 'ws_1', 'external_id' => 'acct_8891']]),
        ]);

        $result = $partner->workspaces()->create(['name' => 'Acme', 'external_id' => 'acct_8891']);

        $this->assertFalse($result->created);
        $this->assertNull($result->oneTimePassword());
    }

    public function test_find_by_external_id_answers_null_without_creating_anything(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $this->assertNull($partner->workspaces()->findByExternalId('acct_unknown'));
        $this->assertSame('GET', $history[1]['request']->getMethod());
        $this->assertStringContainsString('external_id=acct_unknown', $history[1]['request']->getUri()->getQuery());
    }

    public function test_suspend_and_activate_hit_the_workspace_sub_routes(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, ['data' => ['id' => 'ws_1', 'status' => 'suspended']]),
            ResponseFactory::json(200, ['data' => ['id' => 'ws_1', 'status' => 'active']]),
        ], $history);

        $this->assertTrue($partner->workspaces()->suspend('ws_1')->isSuspended());
        $this->assertTrue($partner->workspaces()->activate('ws_1')->isActive());

        $this->assertSame('/api/v1/partner/workspaces/ws_1/suspend', $history[1]['request']->getUri()->getPath());
        $this->assertSame('/api/v1/partner/workspaces/ws_1/activate', $history[2]['request']->getUri()->getPath());
    }

    public function test_adding_an_existing_email_reports_a_reused_account(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            // 200 — Connect already knew this email and simply attached it.
            ResponseFactory::json(200, [
                'data' => ['id' => 'usr_9', 'email' => 'khalid@acme.test', 'role' => 'agent'],
            ]),
        ]);

        $result = $partner->users()->add('ws_1', [
            'name' => 'Khalid',
            'email' => 'khalid@acme.test',
            'role' => 'agent',
            'password_auto' => true,
        ]);

        $this->assertFalse($result->created);
        // A reused account keeps its own password; none is returned, and that
        // is success rather than a missing field.
        $this->assertNull($result->oneTimePassword());
        $this->assertSame('agent', $result->user->role);
    }

    public function test_minting_a_workspace_token_reads_the_identifier_under_either_name(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(201, [
                'data' => [
                    'token' => 'okc_wt_plain',
                    // The mint response's original name — no `id` in sight.
                    'token_id' => 42,
                    'name' => 'Acme product sync',
                    'abilities' => ['read', 'send'],
                ],
            ]),
        ]);

        $token = $partner->tokens()->create('ws_1', 'Acme product sync', 'usr_1', ['read', 'send']);

        $this->assertSame('okc_wt_plain', $token->plainTextToken);
        $this->assertSame('42', $token->id);
        $this->assertTrue($token->can('send'));
    }

    public function test_revoking_a_token_uses_the_numeric_id(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, ['revoked' => true]),
        ], $history);

        $this->assertTrue($partner->tokens()->revoke('ws_1', 42));
        $this->assertSame('DELETE', $history[1]['request']->getMethod());
        $this->assertSame('/api/v1/partner/workspaces/ws_1/tokens/42', $history[1]['request']->getUri()->getPath());
    }

    public function test_reserving_a_channel_returns_it_without_credentials(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(201, [
                'data' => ['id' => 'ch_1', 'type' => 'cloud_api', 'status' => 'disconnected'],
            ]),
        ], $history);

        $channel = $partner->channels()->create('ws_1', 'Sales line', 'cloud_api', [
            'phone_number' => '+966500000000',
        ]);

        $this->assertSame('ch_1', $channel->id);

        $body = json_decode((string) $history[1]['request']->getBody(), true);
        $this->assertSame('Sales line', $body['display_name']);
        $this->assertSame('+966500000000', $body['phone_number']);
    }

    public function test_issuing_a_sign_in_link_returns_a_short_lived_url(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(201, [
                'data' => ['url' => 'https://wa.example.com/partner/sso?ticket=abc', 'expires_in' => 300],
            ]),
        ], $history);

        $link = $partner->sso()->issue('ws_1', 'usr_1', '/app/inbox');

        $this->assertStringContainsString('ticket=abc', $link->url);
        $this->assertSame(300, $link->expiresIn);

        $body = json_decode((string) $history[1]['request']->getBody(), true);
        $this->assertSame('/app/inbox', $body['redirect']);
    }

    public function test_a_workspace_client_needs_the_plain_token_not_a_listed_one(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, ['data' => [['id' => 7, 'name' => 'sync', 'abilities' => ['read']]]]),
        ]);

        $listed = $partner->tokens()->list('ws_1')[0];

        $this->expectException(RuntimeException::class);

        // A token read back from a list carries no secret — the platform kept
        // only a hash — so building a client from it cannot work, and saying
        // so beats a 401 three calls later.
        $partner->workspaceClient($listed);
    }
}
