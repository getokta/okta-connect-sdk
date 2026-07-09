<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\EmailAnalytics;
use Okta\Connect\WhatsApp\DTO\EmailMessage;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class EmailsTest extends TestCase
{
    public function test_send_posts_to_v1_emails_with_idempotency_and_returns_dto(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => [
                    'id' => 'em_1',
                    'from' => 'Acme <no-reply@acme.com>',
                    'from_address' => 'no-reply@acme.com',
                    'to' => ['ali@example.com'],
                    'subject' => 'Welcome',
                    'status' => 'queued',
                    'message_id' => 'mid_1',
                ],
            ]),
        ], $history);

        $email = $client->emails()->send([
            'from' => 'Acme <no-reply@acme.com>',
            'to' => ['ali@example.com'],
            'subject' => 'Welcome',
            'html' => '<b>Hi</b>',
        ], idempotencyKey: 'op-1');

        $this->assertInstanceOf(EmailMessage::class, $email);
        $this->assertSame('em_1', $email->id);
        $this->assertSame('no-reply@acme.com', $email->fromAddress);
        $this->assertSame(['ali@example.com'], $email->to);
        $this->assertSame('queued', $email->status);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/emails', $request->getUri()->getPath());
        $this->assertSame('op-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(['ali@example.com'], $body['to']);
        $this->assertSame('<b>Hi</b>', $body['html']);
    }

    public function test_send_template_builds_body_with_template_and_variables_and_overrides(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'em_2', 'status' => 'queued']]),
        ], $history);

        $client->emails()->sendTemplate(
            'Acme <no-reply@acme.com>',
            ['ali@example.com'],
            'welcome',
            ['name' => 'Ali'],
            ['cc' => ['boss@example.com'], 'subject' => 'Hello Ali'],
            idempotencyKey: 'tpl-1',
        );

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/emails', $request->getUri()->getPath());
        $this->assertSame('tpl-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('Acme <no-reply@acme.com>', $body['from']);
        $this->assertSame(['ali@example.com'], $body['to']);
        $this->assertSame('welcome', $body['template']);
        $this->assertSame(['name' => 'Ali'], $body['variables']);
        $this->assertSame(['boss@example.com'], $body['cc']);
        $this->assertSame('Hello Ali', $body['subject']);
    }

    public function test_list_passes_filters_as_query_string(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'em_1', 'status' => 'delivered']],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->emails()->list(['status' => 'delivered', 'per_page' => 25]);

        $this->assertCount(1, $page);
        $request = $history[0]['request'];
        $this->assertSame('/api/v1/emails', $request->getUri()->getPath());
        $this->assertStringContainsString('status=delivered', $request->getUri()->getQuery());
        $this->assertStringContainsString('per_page=25', $request->getUri()->getQuery());
    }

    public function test_get_hits_the_single_record_route(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'em_9', 'status' => 'sent']]),
        ], $history);

        $email = $client->emails()->get('em_9');

        $this->assertInstanceOf(EmailMessage::class, $email);
        $this->assertSame('em_9', $email->id);
        $this->assertSame('/api/v1/emails/em_9', $history[0]['request']->getUri()->getPath());
    }

    public function test_analytics_builds_from_to_query_and_returns_dto(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    'from' => '2026-01-01',
                    'to' => '2026-01-31',
                    'summary' => [
                        'total' => 100,
                        'delivered' => 95,
                        'bounced' => 3,
                        'delivery_rate' => 0.95,
                        'bounce_rate' => 0.03,
                    ],
                    'series' => [
                        ['date' => '2026-01-01', 'sent' => 10, 'delivered' => 9, 'bounced' => 1],
                    ],
                ],
            ]),
        ], $history);

        $analytics = $client->emails()->analytics('2026-01-01', '2026-01-31');

        $this->assertInstanceOf(EmailAnalytics::class, $analytics);
        $this->assertSame('2026-01-01', $analytics->from);
        $this->assertSame(100, $analytics->total());
        $this->assertSame(0.95, $analytics->deliveryRate());
        $this->assertSame(0.03, $analytics->bounceRate());
        $this->assertCount(1, $analytics->series);

        $request = $history[0]['request'];
        $this->assertSame('/api/v1/emails/analytics', $request->getUri()->getPath());
        $this->assertStringContainsString('from=2026-01-01', $request->getUri()->getQuery());
        $this->assertStringContainsString('to=2026-01-31', $request->getUri()->getQuery());
    }

    public function test_analytics_without_bounds_sends_no_query(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['summary' => [], 'series' => []]]),
        ], $history);

        $client->emails()->analytics();

        $this->assertSame('', $history[0]['request']->getUri()->getQuery());
    }
}
