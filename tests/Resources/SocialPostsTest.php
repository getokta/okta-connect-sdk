<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\SocialPost;
use Okta\Connect\WhatsApp\DTO\SocialPostTarget;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class SocialPostsTest extends TestCase
{
    public function test_list_passes_status_and_per_page_as_query(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    [
                        'id' => 'sp_1',
                        'status' => 'scheduled',
                        'body' => 'Hello world',
                        'targets' => [
                            ['channel_id' => 'ch_1', 'status' => 'pending'],
                            ['channel_id' => 'ch_2', 'status' => 'pending'],
                        ],
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->socialPosts()->list(['status' => 'scheduled', 'per_page' => 25]);

        $this->assertCount(1, $page);

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/social-posts', $request->getUri()->getPath());
        $this->assertStringContainsString('status=scheduled', $request->getUri()->getQuery());
        $this->assertStringContainsString('per_page=25', $request->getUri()->getQuery());
    }

    public function test_list_returns_social_post_with_parsed_targets(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    [
                        'id' => 'sp_1',
                        'status' => 'scheduled',
                        'body' => 'Hello world',
                        'media' => [['url' => 'https://cdn.example.com/a.jpg', 'type' => 'image']],
                        'scheduled_at' => '2026-07-10T10:00:00Z',
                        'target_count' => 2,
                        'published_count' => 0,
                        'failed_count' => 0,
                        'created_at' => '2026-07-09T09:00:00Z',
                        'targets' => [
                            [
                                'channel_id' => 'ch_1',
                                'status' => 'pending',
                                'target_ref' => null,
                                'permalink' => null,
                                'provider_post_id' => null,
                                'published_at' => null,
                            ],
                            [
                                'channel_id' => 'ch_2',
                                'status' => 'published',
                                'target_ref' => 'tweet_123',
                                'permalink' => 'https://x.com/acme/status/123',
                                'provider_post_id' => '123',
                                'published_at' => '2026-07-10T10:00:05Z',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $page = $client->socialPosts()->list();
        $items = iterator_to_array($page);

        $this->assertCount(1, $items);
        $post = $items[0];

        $this->assertInstanceOf(SocialPost::class, $post);
        $this->assertSame('sp_1', $post->id);
        $this->assertSame('scheduled', $post->status);
        $this->assertSame(2, $post->targetCount);
        $this->assertCount(2, $post->targets);
        $this->assertContainsOnlyInstancesOf(SocialPostTarget::class, $post->targets);

        $this->assertSame('ch_1', $post->targets[0]->channelId);
        $this->assertSame('pending', $post->targets[0]->status);

        $this->assertSame('ch_2', $post->targets[1]->channelId);
        $this->assertSame('published', $post->targets[1]->status);
        $this->assertSame('tweet_123', $post->targets[1]->targetRef);
        $this->assertSame('https://x.com/acme/status/123', $post->targets[1]->permalink);
        $this->assertSame('123', $post->targets[1]->providerPostId);
    }

    public function test_get_hits_the_ulid_route(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => ['id' => 'sp_1', 'status' => 'draft', 'body' => 'Hello'],
            ]),
        ], $history);

        $post = $client->socialPosts()->get('sp_1');

        $this->assertSame('sp_1', $post->id);
        $this->assertSame('draft', $post->status);

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/social-posts/sp_1', $request->getUri()->getPath());
    }

    public function test_create_posts_text_and_channel_ids(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'sp_1', 'status' => 'draft', 'body' => 'Hello world'],
            ]),
        ], $history);

        $post = $client->socialPosts()->create([
            'text' => 'Hello world',
            'channel_ids' => ['ch_1', 'ch_2'],
        ], idempotencyKey: 'post-1');

        $this->assertInstanceOf(SocialPost::class, $post);
        $this->assertSame('sp_1', $post->id);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/social-posts', $request->getUri()->getPath());
        $this->assertSame('post-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('Hello world', $body['text']);
        $this->assertSame(['ch_1', 'ch_2'], $body['channel_ids']);
    }

    public function test_schedule_includes_scheduled_at_in_body(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'sp_2', 'status' => 'scheduled', 'body' => 'Launch day!'],
            ]),
        ], $history);

        $client->socialPosts()->schedule(
            'Launch day!',
            ['ch_1'],
            scheduledAt: '2026-07-10T10:00:00Z',
        );

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame('Launch day!', $body['text']);
        $this->assertSame(['ch_1'], $body['channel_ids']);
        $this->assertSame('2026-07-10T10:00:00Z', $body['scheduled_at']);
        $this->assertArrayNotHasKey('media', $body);
    }

    public function test_draft_omits_scheduled_at(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'sp_3', 'status' => 'draft', 'body' => 'Draft copy'],
            ]),
        ], $history);

        $client->socialPosts()->draft('Draft copy', ['ch_1', 'ch_2']);

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame('Draft copy', $body['text']);
        $this->assertSame(['ch_1', 'ch_2'], $body['channel_ids']);
        $this->assertArrayNotHasKey('scheduled_at', $body);
        $this->assertArrayNotHasKey('media', $body);
    }

    public function test_media_is_included_only_when_provided(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'sp_4', 'status' => 'draft', 'body' => 'With media'],
            ]),
        ], $history);

        $client->socialPosts()->schedule(
            'With media',
            ['ch_1'],
            media: [['url' => 'https://cdn.example.com/a.jpg', 'type' => 'image']],
        );

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        $this->assertSame(
            [['url' => 'https://cdn.example.com/a.jpg', 'type' => 'image']],
            $body['media'],
        );
        $this->assertArrayNotHasKey('scheduled_at', $body);
    }
}
