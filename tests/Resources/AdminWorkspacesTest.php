<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Workspace;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class AdminWorkspacesTest extends TestCase
{
    public function test_create_posts_to_admin_endpoint(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => '01HABC', 'name' => 'Acme', 'slug' => 'acme'],
            ]),
        ], $history);

        $workspace = $client->admin()->workspaces()->create(['name' => 'Acme', 'slug' => 'acme']);

        $this->assertInstanceOf(Workspace::class, $workspace);
        $this->assertSame('01HABC', $workspace->id);
        $this->assertSame('/api/v1/admin/workspaces', $history[0]['request']->getUri()->getPath());
        $this->assertSame('POST', $history[0]['request']->getMethod());
    }

    public function test_list_returns_paginated_workspaces(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => '01H1', 'name' => 'A'],
                    ['id' => '01H2', 'name' => 'B'],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ]);

        $page = $client->admin()->workspaces()->list(['per_page' => 20]);

        $this->assertCount(2, $page);
        $this->assertFalse($page->hasMore());
    }

    public function test_get_uses_ulid_path(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => '01HABC', 'name' => 'Acme']]),
        ], $history);

        $client->admin()->workspaces()->get('01HABC');

        $this->assertSame('/api/v1/admin/workspaces/01HABC', $history[0]['request']->getUri()->getPath());
    }

    public function test_update_uses_patch(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => '01HABC', 'display_name' => 'Acme Inc']]),
        ], $history);

        $workspace = $client->admin()->workspaces()->update('01HABC', ['display_name' => 'Acme Inc']);

        $this->assertSame('Acme Inc', $workspace->displayName);
        $this->assertSame('PATCH', $history[0]['request']->getMethod());
    }
}
