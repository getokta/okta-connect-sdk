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
