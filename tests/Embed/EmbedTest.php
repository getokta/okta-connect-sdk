<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Embed;

use InvalidArgumentException;
use Okta\Connect\WhatsApp\Embed\Embed;
use Okta\Connect\WhatsApp\Embed\EmbedUser;
use Okta\Connect\WhatsApp\Embed\UiHide;
use PHPUnit\Framework\TestCase;

final class EmbedTest extends TestCase
{
    private const SECRET = 'a0a0a0a0a0a0a0a0a0a0a0a0a0a0a0a0';

    private function embed(): Embed
    {
        return new Embed('https://connect.example.com/', self::SECRET);
    }

    private function user(): EmbedUser
    {
        return new EmbedUser('u-1', 'Op@Acme.com', 'Operator');
    }

    /** @return array<string, mixed> */
    private function decodePayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts);

        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);

        return json_decode((string) $json, true);
    }

    public function test_sso_token_carries_expected_claims_and_signature(): void
    {
        $jwt = $this->embed()->ssoToken($this->user(), Embed::SCOPE_ADMIN);
        $parts = explode('.', $jwt);

        // Signature is HS256 over header.payload with the shared secret.
        $expected = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $parts[0].'.'.$parts[1], self::SECRET, true)
        ), '+/', '-_'), '=');
        $this->assertSame($expected, $parts[2]);

        $payload = $this->decodePayload($jwt);
        $this->assertSame('okta-web', $payload['iss']);
        $this->assertSame('okta-whatsapp', $payload['aud']);
        $this->assertSame('u-1', $payload['sub']);
        $this->assertSame('Op@Acme.com', $payload['email']);
        $this->assertSame('platform.admin', $payload['scope']);
        $this->assertArrayHasKey('jti', $payload);
        $this->assertSame(60, $payload['exp'] - $payload['iat']);
        $this->assertArrayNotHasKey('ui_hide', $payload);
    }

    public function test_sso_url_assembles_token_and_redirect(): void
    {
        $url = $this->embed()->ssoUrl($this->user(), '/app/inbox?embedded=1');

        $this->assertStringStartsWith('https://connect.example.com/embed/sso?token=', $url);
        $this->assertStringContainsString('&redirect='.rawurlencode('/app/inbox?embedded=1'), $url);
    }

    public function test_session_token_allows_long_ttl(): void
    {
        $jwt = $this->embed()->sessionToken($this->user(), Embed::SCOPE_INBOX, [], 14400);
        $payload = $this->decodePayload($jwt);

        $this->assertSame(14400, $payload['exp'] - $payload['iat']);
        $this->assertSame('platform.inbox', $payload['scope']);
    }

    public function test_embed_url_appends_embed_token_preserving_query(): void
    {
        $url = $this->embed()->embedUrl('/app/inbox?embedded=1', $this->user());

        $this->assertStringContainsString('/app/inbox?embedded=1&embed_token=', $url);
    }

    public function test_inbox_url_uses_inbox_scope_and_default_path(): void
    {
        $url = $this->embed()->inboxUrl($this->user());

        $this->assertStringContainsString('https://connect.example.com/app/inbox?embedded=1&embed_token=', $url);

        // Pull the token back out and confirm the scope.
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $payload = $this->decodePayload($q['embed_token']);
        $this->assertSame('platform.inbox', $payload['scope']);
    }

    public function test_ui_hide_keys_are_embedded_when_valid(): void
    {
        $jwt = $this->embed()->ssoToken($this->user(), Embed::SCOPE_ADMIN, [
            UiHide::AI,
            UiHide::SIDEBAR,
            UiHide::AI, // duplicate — should be de-duped
        ]);

        $payload = $this->decodePayload($jwt);
        $this->assertSame(['ai', 'sidebar'], $payload['ui_hide']);
    }

    public function test_unknown_ui_hide_key_is_rejected_at_mint_time(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->embed()->ssoToken($this->user(), Embed::SCOPE_ADMIN, ['ai', 'hide_everything']);
    }

    public function test_sso_ttl_ceiling_is_enforced(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->embed()->ssoToken($this->user(), Embed::SCOPE_ADMIN, [], 301);
    }

    public function test_session_ttl_ceiling_is_enforced(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->embed()->sessionToken($this->user(), Embed::SCOPE_ADMIN, [], 14401);
    }

    public function test_unknown_scope_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->embed()->ssoToken($this->user(), 'platform.root');
    }

    public function test_token_header_shape(): void
    {
        $this->assertSame(['X-Embed-Token' => 'abc.def.ghi'], $this->embed()->tokenHeader('abc.def.ghi'));
    }

    public function test_empty_secret_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Embed('https://connect.example.com', '');
    }

    public function test_empty_user_fields_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EmbedUser('', 'op@acme.com');
    }

    public function test_client_embed_factory_uses_configured_base_url(): void
    {
        $client = new \Okta\Connect\WhatsApp\Client('https://tenant.example.com', 'api-token');
        $url = $client->embed(self::SECRET)->ssoUrl($this->user());

        $this->assertStringStartsWith('https://tenant.example.com/embed/sso?token=', $url);
    }
}
