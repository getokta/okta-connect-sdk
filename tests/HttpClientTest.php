<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests;

use Okta\Connect\WhatsApp\Exceptions\AuthenticationException;
use Okta\Connect\WhatsApp\Exceptions\AuthorizationException;
use Okta\Connect\WhatsApp\Exceptions\NotFoundException;
use Okta\Connect\WhatsApp\Exceptions\RateLimitException;
use Okta\Connect\WhatsApp\Exceptions\ServerException;
use Okta\Connect\WhatsApp\Exceptions\ValidationException;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    public function test_get_attaches_auth_and_accept_headers(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->channels()->list();

        $request = $history[0]['request'];
        $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('https://wa.example.com/api/v1/channels', (string) $request->getUri());
    }

    public function test_401_raises_authentication_exception(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(401, ['message' => 'Unauthenticated.']),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $client->channels()->list();
    }

    public function test_403_raises_authorization_exception(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(403, ['message' => 'Forbidden.']),
        ]);

        $this->expectException(AuthorizationException::class);
        $client->channels()->list();
    }

    public function test_404_raises_not_found_exception(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(404, ['message' => 'Not found.']),
        ]);

        $this->expectException(NotFoundException::class);
        $client->channels()->get('missing');
    }

    public function test_422_exposes_field_errors(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(422, [
                'message' => 'The given data was invalid.',
                'errors' => ['phone' => ['The phone field is required.']],
            ]),
        ]);

        try {
            $client->contacts()->upsert([]);
            $this->fail('expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->statusCode());
            $this->assertSame(['phone' => ['The phone field is required.']], $e->errors());
        }
    }

    public function test_429_exposes_retry_after(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(429, ['message' => 'Too many.'], ['Retry-After' => '7']),
        ]);

        try {
            $client->channels()->list();
            $this->fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(7, $e->retryAfter());
        }
    }

    public function test_500_raises_server_exception(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(500, ['message' => 'Boom.']),
        ]);

        $this->expectException(ServerException::class);
        $client->channels()->list();
    }

    public function test_unknown_4xx_falls_back_to_base_exception(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(418, ['message' => "I'm a teapot."]),
        ]);

        try {
            $client->channels()->list();
            $this->fail('expected WhatsAppException');
        } catch (WhatsAppException $e) {
            $this->assertSame(418, $e->statusCode());
            $this->assertNotNull($e->response());
        }
    }
}
