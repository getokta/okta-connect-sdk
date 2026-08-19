<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Partner;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Okta\Connect\WhatsApp\Exceptions\AuthenticationException;
use Okta\Connect\WhatsApp\Partner\PartnerClient;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

/**
 * The token lifecycle, which is the whole reason this transport exists.
 */
final class PartnerTransportTest extends TestCase
{
    public function test_it_exchanges_the_key_pair_once_and_reuses_the_token(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken('okc_pat_live'),
            ResponseFactory::json(200, ['data' => ['partner' => ['id' => 'p1']]]),
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $partner->me();
        $partner->workspaces()->list();

        $this->assertCount(3, $history);
        $this->assertSame('/api/v1/partner/token', $history[0]['request']->getUri()->getPath());

        // The exchange itself carries no bearer — the key pair in the body is
        // the credential.
        $this->assertSame('', $history[0]['request']->getHeaderLine('Authorization'));

        foreach ([1, 2] as $index) {
            $this->assertSame(
                'Bearer okc_pat_live',
                $history[$index]['request']->getHeaderLine('Authorization'),
            );
        }
    }

    public function test_it_re_exchanges_once_when_a_call_comes_back_401(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken('okc_pat_stale'),
            ResponseFactory::json(401, ['error' => 'invalid_token']),
            ResponseFactory::partnerToken('okc_pat_fresh'),
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $partner->workspaces()->list();

        $this->assertCount(4, $history);
        $this->assertSame('/api/v1/partner/token', $history[2]['request']->getUri()->getPath());
        $this->assertSame('Bearer okc_pat_fresh', $history[3]['request']->getHeaderLine('Authorization'));
    }

    public function test_it_gives_up_after_one_retry_rather_than_looping(): void
    {
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::partnerToken('okc_pat_stale'),
            ResponseFactory::json(401, ['error' => 'invalid_token']),
            ResponseFactory::partnerToken('okc_pat_fresh'),
            ResponseFactory::json(401, ['error' => 'invalid_token']),
        ]);

        $this->expectException(AuthenticationException::class);

        $partner->workspaces()->list();
    }

    public function test_a_static_token_is_used_verbatim_and_never_exchanged(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history, 'okc_pst_dev');

        $partner->workspaces()->list();

        $this->assertCount(1, $history);
        $this->assertSame('/api/v1/partner/workspaces', $history[0]['request']->getUri()->getPath());
        $this->assertSame('Bearer okc_pst_dev', $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function test_a_static_token_does_not_try_to_refresh_on_401(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            ResponseFactory::json(401, ['error' => 'invalid_token']),
        ], $history, 'okc_pst_dev');

        try {
            $partner->workspaces()->list();
            $this->fail('expected an AuthenticationException');
        } catch (AuthenticationException) {
            // There is nothing to exchange, so the 401 is the answer.
            $this->assertCount(1, $history);
        }
    }

    public function test_it_refreshes_before_a_near_expiry_token_is_used(): void
    {
        $history = [];
        $partner = ResponseFactory::makePartnerClient([
            // Inside the 60s skew window the moment it is issued.
            ResponseFactory::partnerToken('okc_pat_short', 30),
            ResponseFactory::partnerToken('okc_pat_next', 3600),
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $partner->accessToken();
        $partner->workspaces()->list();

        $this->assertSame('Bearer okc_pat_next', $history[2]['request']->getHeaderLine('Authorization'));
    }

    public function test_it_narrows_the_exchanged_token_when_abilities_are_named(): void
    {
        $history = [];
        $partner = PartnerClient::withKeyPair(
            'https://wa.example.com',
            'okc_ci_test',
            'okc_cs_test',
            ['retries' => 0, 'httpClient' => self::guzzleFor([
                ResponseFactory::partnerToken(),
                ResponseFactory::json(200, ['data' => []]),
            ], $history)],
            ['workspaces.read'],
        );

        $partner->workspaces()->list();

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame('client_credentials', $body['grant_type']);
        $this->assertSame(['workspaces.read'], $body['abilities']);
    }

    /**
     * @param  list<Response>  $queue
     * @param  array<int, array<string, mixed>>  $history
     */
    private static function guzzleFor(array $queue, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($queue));
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack, 'http_errors' => false]);
    }
}
