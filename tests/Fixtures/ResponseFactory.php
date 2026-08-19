<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Fixtures;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Okta\Connect\WhatsApp\Client;
use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\Http\HttpClient;
use Okta\Connect\WhatsApp\Partner\PartnerClient;

/**
 * Boilerplate for spinning up a Client around a Guzzle MockHandler.
 */
final class ResponseFactory
{
    /**
     * @param  list<Response>                       $queue
     * @param  array<int, array<string, mixed>>     $history Reference; appended to per request.
     */
    public static function makeClient(array $queue, array &$history = []): Client
    {
        $mock = new MockHandler($queue);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $guzzle = new GuzzleClient([
            'handler' => $stack,
            'http_errors' => false,
        ]);

        $config = new Config(
            baseUrl: 'https://wa.example.com',
            token: 'test-token',
            timeout: 5,
            retries: 0,
            httpClient: $guzzle,
        );

        return Client::fromConfig($config, new HttpClient($config));
    }

    /**
     * A PartnerClient wired to the same mock handler.
     *
     * The transport is left to build its own HTTP clients so the token
     * exchange, the refresh and the Authorization header it attaches are all
     * exercised for real — those are the parts worth testing.
     *
     * @param  list<Response>                    $queue
     * @param  array<int, array<string, mixed>>  $history  Reference; appended to per request.
     */
    public static function makePartnerClient(
        array $queue,
        array &$history = [],
        ?string $staticToken = null,
    ): PartnerClient {
        $mock = new MockHandler($queue);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $options = [
            'timeout' => 5,
            'retries' => 0,
            'httpClient' => new GuzzleClient(['handler' => $stack, 'http_errors' => false]),
        ];

        return $staticToken === null
            ? PartnerClient::withKeyPair('https://wa.example.com', 'okc_ci_test', 'okc_cs_test', $options)
            : PartnerClient::withStaticToken('https://wa.example.com', $staticToken, $options);
    }

    /**
     * The body `POST /api/v1/partner/token` answers with.
     *
     * @param  list<string>  $abilities
     */
    public static function partnerToken(string $token = 'okc_pat_first', int $expiresIn = 3600, array $abilities = ['workspaces.write']): Response
    {
        return self::json(200, [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'expires_at' => gmdate('c', time() + $expiresIn),
            'abilities' => $abilities,
        ]);
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $body
     */
    public static function json(int $status, array $body, array $headers = []): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'] + $headers,
            (string) json_encode($body),
        );
    }
}
