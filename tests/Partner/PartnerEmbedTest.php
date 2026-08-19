<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Partner;

use Okta\Connect\WhatsApp\Embed\EmbedUser;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

/**
 * The embed key a partner signs iframe sessions with — and the two things
 * that most often go wrong with it: the wrong issuer, and an origin that
 * silently did not parse.
 */
final class PartnerEmbedTest extends TestCase
{
    public function test_issuing_a_key_returns_the_secret_and_the_issuer_to_sign_with(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(201, [
                'data' => [
                    'secret' => 'okc_es_live',
                    'secret_id' => '01J',
                    'issuer' => 'partner:01HZK',
                    'audience' => 'okta-whatsapp',
                    'origins' => ['https://mygurb.com'],
                ],
            ]),
        ], $history);

        $secret = $partner->embed()->issueSecret(['https://mygurb.com']);

        $this->assertSame('okc_es_live', $secret->secret);
        $this->assertSame('partner:01HZK', $secret->issuer);
        $this->assertSame('/api/v1/partner/embed/secret', $history[1]['request']->getUri()->getPath());
    }

    public function test_replacing_origins_surfaces_what_the_platform_refused(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, [
                'data' => [
                    'origins' => ['https://mygurb.com'],
                    'rejected' => ['*', 'not a url at all'],
                ],
            ]),
        ], $history);

        $result = $partner->embed()->setOrigins(['mygurb.com', '*', 'not a url at all']);

        $this->assertTrue($result->hasRejections());
        $this->assertSame(['*', 'not a url at all'], $result->rejected);
        $this->assertSame('PUT', $history[1]['request']->getMethod());
    }

    public function test_the_signer_derives_the_partner_issuer_instead_of_trusting_a_typed_one(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, [
                'data' => [
                    'issued' => true,
                    'issuer' => 'partner:01HZK',
                    'audience' => 'okta-whatsapp',
                    'origins' => ['https://mygurb.com'],
                ],
            ]),
        ]);

        $embed = $partner->embedSigner('okc_es_live');
        $token = $embed->sessionToken(new EmbedUser('gurb-1', 'operator@mygurb.test', 'Operator'));

        $this->assertSame('partner:01HZK', self::claims($token)['iss']);
    }

    public function test_a_named_workspace_rides_along_as_the_workspace_claim(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken(),
            ResponseFactory::json(200, ['data' => ['issued' => true, 'issuer' => 'partner:01HZK']]),
        ]);

        $embed = $partner->embedSigner('okc_es_live');

        $user = new EmbedUser('gurb-1', 'operator@mygurb.test', 'Operator', 'ws_second');
        $claims = self::claims($embed->sessionToken($user));

        $this->assertSame('ws_second', $claims['workspace']);

        // Omitted entirely when there is nothing to name — the platform then
        // falls back to the oldest membership rather than reading an empty
        // string as a workspace id.
        $plain = self::claims($embed->sessionToken($user->inWorkspace(null)));
        $this->assertArrayNotHasKey('workspace', $plain);
    }

    /**
     * @return array<string, mixed>
     */
    private static function claims(string $jwt): array
    {
        $body = explode('.', $jwt)[1];
        $json = base64_decode(strtr($body, '-_', '+/'), true);

        /** @var array<string, mixed> $claims */
        $claims = json_decode((string) $json, true);

        return $claims;
    }
}
