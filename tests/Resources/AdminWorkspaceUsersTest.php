<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Tests\Resources;

use Okta\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class AdminWorkspaceUsersTest extends TestCase
{
    public function test_create_user_under_workspace(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 'u_1', 'workspace_id' => '01H', 'email' => 'ali@acme.com'],
            ]),
        ], $history);

        $user = $client->admin()->workspaceUsers()->create('01H', [
            'name' => 'Ali', 'email' => 'ali@acme.com', 'password_auto' => true,
        ]);

        $this->assertSame('u_1', $user->id);
        $this->assertSame('/api/v1/admin/workspaces/01H/users', $history[0]['request']->getUri()->getPath());
    }

    public function test_list_users_uses_workspace_scoped_path(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => []]),
        ], $history);

        $client->admin()->workspaceUsers()->list('01H');

        $this->assertSame('/api/v1/admin/workspaces/01H/users', $history[0]['request']->getUri()->getPath());
    }
}
