<?php

declare(strict_types=1);

namespace Okta\WhatsApp\Tests\Resources;

use Okta\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class AdminWorkspaceTokensTest extends TestCase
{
    public function test_issue_returns_token_with_plaintext(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => [
                    'id' => 't_1',
                    'workspace_id' => '01H',
                    'name' => 'partner-app',
                    'abilities' => ['read', 'send'],
                    'plain_text_token' => 'abc.def',
                ],
            ]),
        ], $history);

        $token = $client->admin()->workspaceTokens()->issue('01H', [
            'name' => 'partner-app', 'abilities' => ['read', 'send'],
        ]);

        $this->assertSame('abc.def', $token->plainTextToken);
        $this->assertSame(['read', 'send'], $token->abilities);
        $this->assertSame('/api/v1/admin/workspaces/01H/tokens', $history[0]['request']->getUri()->getPath());
    }

    public function test_issue_accepts_token_field_alias(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 't_1', 'token' => 'plain.text', 'abilities' => []],
            ]),
        ]);

        $token = $client->admin()->workspaceTokens()->issue('01H', ['name' => 'x']);

        $this->assertSame('plain.text', $token->plainTextToken);
    }
}
