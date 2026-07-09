<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\EmailTemplate;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class EmailTemplatesTest extends TestCase
{
    public function test_list_passes_filters_as_query_string(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [['id' => 'tpl_1', 'name' => 'Welcome', 'slug' => 'welcome']],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ], $history);

        $page = $client->emails()->templates()->list(['per_page' => 10]);

        $this->assertCount(1, $page);
        $request = $history[0]['request'];
        $this->assertSame('/api/v1/email-templates', $request->getUri()->getPath());
        $this->assertStringContainsString('per_page=10', $request->getUri()->getQuery());
    }

    public function test_get_returns_template_dto(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => ['id' => 'tpl_1', 'name' => 'Welcome', 'slug' => 'welcome', 'variables' => ['name' => 'string']],
            ]),
        ], $history);

        $template = $client->emails()->templates()->get('tpl_1');

        $this->assertInstanceOf(EmailTemplate::class, $template);
        $this->assertSame('welcome', $template->slug);
        $this->assertSame(['name' => 'string'], $template->variables);
        $this->assertSame('/api/v1/email-templates/tpl_1', $history[0]['request']->getUri()->getPath());
    }

    public function test_create_posts_to_templates_collection(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 'tpl_2', 'name' => 'Receipt', 'slug' => 'receipt']]),
        ], $history);

        $template = $client->emails()->templates()->create([
            'name' => 'Receipt',
            'slug' => 'receipt',
            'subject' => 'Your receipt',
            'html' => '<p>Thanks</p>',
        ], idempotencyKey: 'ct-1');

        $this->assertInstanceOf(EmailTemplate::class, $template);
        $this->assertSame('tpl_2', $template->id);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/email-templates', $request->getUri()->getPath());
        $this->assertSame('ct-1', $request->getHeaderLine('Idempotency-Key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('Receipt', $body['name']);
        $this->assertSame('receipt', $body['slug']);
    }

    public function test_update_uses_patch(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'tpl_2', 'name' => 'Receipt v2', 'slug' => 'receipt']]),
        ], $history);

        $template = $client->emails()->templates()->update('tpl_2', ['name' => 'Receipt v2']);

        $this->assertSame('Receipt v2', $template->name);

        $request = $history[0]['request'];
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('/api/v1/email-templates/tpl_2', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('Receipt v2', $body['name']);
    }

    public function test_delete_returns_true_on_deleted_flag(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['deleted' => true]),
        ], $history);

        $result = $client->emails()->templates()->delete('tpl_2');

        $this->assertTrue($result);

        $request = $history[0]['request'];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/api/v1/email-templates/tpl_2', $request->getUri()->getPath());
    }
}
