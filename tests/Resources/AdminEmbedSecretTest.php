<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdminEmbedSecretTest extends TestCase
{
    public function test_sync_returns_secret(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['secret' => str_repeat('a', 64)]),
        ], $history);

        $secret = $client->admin()->embedSecret()->sync();

        $this->assertSame(str_repeat('a', 64), $secret);
        $this->assertSame('/api/v1/admin/embed-secret/sync', $history[0]['request']->getUri()->getPath());
        $this->assertSame('POST', $history[0]['request']->getMethod());
    }

    public function test_provision_posts_label_and_issuer(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'label' => 'salla',
                'issuer' => 'salla-app',
                'secret' => str_repeat('b', 64),
                'created' => true,
            ]),
        ], $history);

        $result = $client->admin()->embedSecret()->provision('salla', 'salla-app');

        $this->assertSame('salla', $result['label']);
        $this->assertSame('salla-app', $result['issuer']);
        $this->assertSame(str_repeat('b', 64), $result['secret']);
        $this->assertTrue($result['created']);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/admin/embed-secret/provision', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('salla', $body['label']);
        $this->assertSame('salla-app', $body['issuer']);
    }

    public function test_provision_throws_on_empty_secret(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['label' => 'salla', 'issuer' => 'salla-app']),
        ]);

        $this->expectException(RuntimeException::class);
        $client->admin()->embedSecret()->provision('salla', 'salla-app');
    }
}
