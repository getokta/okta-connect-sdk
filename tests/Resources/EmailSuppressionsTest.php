<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\EmailSuppression;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class EmailSuppressionsTest extends TestCase
{
    public function test_list_passes_reason_filter_as_query_string(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'sp_1', 'address' => 'bounced@example.com', 'reason' => 'bounce']],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->emails()->suppressions()->list(['reason' => 'bounce', 'per_page' => 50]);

        $this->assertCount(1, $page);
        $request = $history[0]['request'];
        $this->assertSame('/api/v1/emails/suppressions', $request->getUri()->getPath());
        $this->assertStringContainsString('reason=bounce', $request->getUri()->getQuery());
    }

    public function test_add_posts_the_address(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'sp_2', 'address' => 'block@example.com', 'reason' => 'manual'],
            ]),
        ], $history);

        $suppression = $client->emails()->suppressions()->add('block@example.com', idempotencyKey: 'sp-1');

        $this->assertInstanceOf(EmailSuppression::class, $suppression);
        $this->assertSame('block@example.com', $suppression->address);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/emails/suppressions', $request->getUri()->getPath());
        $this->assertSame('sp-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('block@example.com', $body['address']);
    }

    public function test_remove_deletes_and_rawurlencodes_the_segment(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['deleted' => true]),
        ], $history);

        $result = $client->emails()->suppressions()->remove('block@example.com');

        $this->assertTrue($result);

        $request = $history[0]['request'];
        $this->assertSame('DELETE', $request->getMethod());
        // The `@` must be percent-encoded in the path.
        $this->assertSame('/api/v1/emails/suppressions/block%40example.com', $request->getUri()->getPath());
    }

    public function test_remove_returns_false_when_not_deleted(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['deleted' => false]),
        ]);

        $this->assertFalse($client->emails()->suppressions()->remove('sp_404'));
    }
}
