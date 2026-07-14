<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Connect;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\Connect\Connect;
use Okta\Connect\WhatsApp\DTO\AccessToken;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;
use Okta\Connect\WhatsApp\Http\HttpClient;
use PHPUnit\Framework\TestCase;

final class ConnectTest extends TestCase
{
    /**
     * @param  list<GuzzleResponse>              $queue
     * @param  array<int, array<string, mixed>>  $history
     */
    private function connect(array $queue = [], array &$history = []): Connect
    {
        $mock = new MockHandler($queue);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);
        $config = new Config(baseUrl: 'https://connect.getokta.io', token: '', retries: 0, httpClient: $guzzle);

        return new Connect('https://connect.getokta.io', new HttpClient($config));
    }

    public function test_authorization_url_encodes_all_params(): void
    {
        $url = $this->connect()->authorizationUrl(
            appName: 'My CRM',
            redirectUri: 'https://crm.example.com/oktawa/callback',
            abilities: ['read', 'send'],
            state: 'opaque-csrf-token',
        );

        $this->assertStringStartsWith('https://connect.getokta.io/connect?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('My CRM', $q['app_name']);
        $this->assertSame('https://crm.example.com/oktawa/callback', $q['redirect_uri']);
        $this->assertSame('read,send', $q['abilities']);
        $this->assertSame('opaque-csrf-token', $q['state']);
    }

    public function test_authorization_url_drops_unknown_abilities_and_empty_state(): void
    {
        $url = $this->connect()->authorizationUrl(
            appName: 'App',
            redirectUri: 'https://app.test/cb',
            abilities: ['read', 'bogus', 'admin'],
        );

        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('read,admin', $q['abilities']);
        $this->assertArrayNotHasKey('state', $q);
    }

    public function test_authorization_url_defaults_to_read_when_no_valid_ability(): void
    {
        $url = $this->connect()->authorizationUrl('App', 'https://app.test/cb', ['nope']);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('read', $q['abilities']);
    }

    public function test_generate_state_is_unguessable_and_unique(): void
    {
        $a = Connect::generateState();
        $b = Connect::generateState();

        $this->assertNotSame($a, $b);
        $this->assertSame(48, strlen($a)); // 24 bytes hex
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $a);
    }

    public function test_exchange_posts_code_and_returns_access_token(): void
    {
        $history = [];
        $connect = $this->connect([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'data' => [
                    'access_token' => '1|abc123',
                    'token_type' => 'Bearer',
                    'abilities' => ['read', 'send'],
                    'expires_at' => '2026-10-12T00:00:00+00:00',
                ],
            ])),
        ], $history);

        $token = $connect->exchange('4f9e2c', 'https://crm.example.com/oktawa/callback');

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame('1|abc123', $token->accessToken);
        $this->assertSame('Bearer', $token->tokenType);
        $this->assertSame(['read', 'send'], $token->abilities);
        $this->assertTrue($token->can('send'));
        $this->assertFalse($token->can('admin'));

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/oauth/token', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('4f9e2c', $body['code']);
        $this->assertSame('https://crm.example.com/oktawa/callback', $body['redirect_uri']);
    }

    public function test_handle_callback_verifies_state_then_exchanges(): void
    {
        $connect = $this->connect([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'data' => ['access_token' => '2|zzz', 'abilities' => ['read']],
            ])),
        ]);

        $token = $connect->handleCallback(
            ['code' => 'the-code', 'state' => 'expected'],
            'https://app.test/cb',
            'expected',
        );

        $this->assertSame('2|zzz', $token->accessToken);
    }

    public function test_handle_callback_rejects_state_mismatch(): void
    {
        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('state mismatch');

        $this->connect()->handleCallback(
            ['code' => 'x', 'state' => 'attacker'],
            'https://app.test/cb',
            'expected',
        );
    }

    public function test_handle_callback_surfaces_access_denied(): void
    {
        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('access_denied');

        $this->connect()->handleCallback(
            ['error' => 'access_denied'],
            'https://app.test/cb',
        );
    }

    public function test_handle_callback_requires_a_code(): void
    {
        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('no code');

        $this->connect()->handleCallback(['state' => 'expected'], 'https://app.test/cb', 'expected');
    }

    public function test_exchange_throws_on_invalid_code(): void
    {
        $connect = $this->connect([
            new GuzzleResponse(400, ['Content-Type' => 'application/json'], (string) json_encode([
                'error' => 'invalid_grant',
                'message' => 'Authorization code is invalid, used, or expired.',
            ])),
        ]);

        $this->expectException(WhatsAppException::class);
        $connect->exchange('bad', 'https://app.test/cb');
    }

    public function test_client_connect_factory_returns_connect(): void
    {
        $this->assertInstanceOf(Connect::class, \Okta\Connect\WhatsApp\Client::connect('https://connect.getokta.io'));
    }
}
