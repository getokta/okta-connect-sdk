<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Message;
use Okta\Connect\WhatsApp\DTO\Template;
use Okta\Connect\WhatsApp\DTO\TemplatePreset;
use Okta\Connect\WhatsApp\Enums\MessageType;
use Okta\Connect\WhatsApp\Exceptions\NotFoundException;
use Okta\Connect\WhatsApp\Exceptions\ValidationException;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class TemplatesTest extends TestCase
{
    public function test_list_returns_templates_and_forwards_filters(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    ['id' => 't_1', 'name' => 'order_ready', 'language' => 'ar', 'status' => 'APPROVED'],
                    ['id' => 't_2', 'name' => 'welcome', 'language' => 'en', 'status' => 'APPROVED'],
                ],
            ]),
        ], $history);

        $templates = $client->templates()->list(['status' => 'APPROVED', 'language' => 'ar']);

        $this->assertCount(2, $templates);
        $this->assertInstanceOf(Template::class, $templates[0]);
        $this->assertSame('order_ready', $templates[0]->name);

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/templates', $request->getUri()->getPath());
        $this->assertStringContainsString('status=APPROVED', $request->getUri()->getQuery());
        $this->assertStringContainsString('language=ar', $request->getUri()->getQuery());
    }

    public function test_send_posts_to_templates_send_and_returns_message(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [
                    'id' => 'm_1',
                    'channel_id' => 'ch_1',
                    'type' => 'template',
                    'status' => 'queued',
                ],
            ]),
        ], $history);

        $message = $client->templates()->send([
            'channel_id' => 'ch_1',
            'wa_id' => '966500000000',
            'template_name' => 'order_ready',
            'language' => 'ar',
            'variables' => ['12345', '120 SAR'],
        ], idempotencyKey: 'tpl-1');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('m_1', $message->id);
        $this->assertSame(MessageType::Template, $message->type);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/templates/send', $request->getUri()->getPath());
        $this->assertSame('tpl-1', $request->getHeaderLine('Idempotency-Key'));
    }

    public function test_list_forwards_category_group_and_preset_filters_and_maps_new_fields(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [[
                    'id' => 't_1',
                    'name' => 'order_shipped',
                    'language' => 'en',
                    'category' => 'utility',
                    'status' => 'draft',
                    'group' => 'Orders',
                    'preset_key' => 'order_shipped',
                    'channel_id' => 'ch_1',
                    'example_variables' => ['Sara', '12345'],
                ]],
            ]),
        ], $history);

        $templates = $client->templates()->list(['category' => 'UTILITY', 'group' => 'Orders', 'preset_key' => 'order_shipped']);

        $this->assertSame('Orders', $templates[0]->group);
        $this->assertSame('order_shipped', $templates[0]->presetKey);
        $this->assertSame('ch_1', $templates[0]->channelId);
        $this->assertSame(['Sara', '12345'], $templates[0]->exampleVariables);
        $this->assertTrue($templates[0]->isFromPreset());
        $this->assertSame([], $templates[0]->extra);

        $query = $history[0]['request']->getUri()->getQuery();
        $this->assertStringContainsString('category=UTILITY', $query);
        $this->assertStringContainsString('group=Orders', $query);
        $this->assertStringContainsString('preset_key=order_shipped', $query);
    }

    public function test_save_posts_definition_including_a_cleared_group(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['data' => ['id' => 't_1', 'name' => 'notice', 'group' => null, 'status' => 'draft']]),
        ], $history);

        $template = $client->templates()->save([
            'name' => 'notice',
            'language' => 'ar',
            'body_text' => 'Hello {{1}}, thanks for your order.',
            'example_variables' => ['Sara'],
            'group' => null,
        ]);

        $this->assertSame('t_1', $template->id);
        $this->assertNull($template->group);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/templates', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertArrayHasKey('group', $body);
        $this->assertNull($body['group']);
    }

    public function test_submit_posts_the_optional_channel(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 't_1', 'status' => 'pending']]),
            ResponseFactory::json(200, ['data' => ['id' => 't_1', 'status' => 'pending']]),
        ], $history);

        $this->assertSame('pending', $client->templates()->submit('t_1', 'ch_9')->status);
        $client->templates()->submit('t_1');

        $this->assertSame('/api/v1/templates/t_1/submit', $history[0]['request']->getUri()->getPath());
        $this->assertSame(['channel_id' => 'ch_9'], json_decode((string) $history[0]['request']->getBody(), true));
        $this->assertArrayNotHasKey('channel_id', (array) json_decode((string) $history[1]['request']->getBody(), true));
    }

    public function test_submit_surfaces_no_template_capable_channel_as_validation_error(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(422, ['message' => 'no_template_capable_channel']),
        ]);

        $this->expectException(ValidationException::class);
        $client->templates()->submit('t_1');
    }

    public function test_delete_returns_the_platform_answer(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['deleted' => true]),
        ], $history);

        $this->assertTrue($client->templates()->delete('t_1'));
        $this->assertSame('DELETE', $history[0]['request']->getMethod());
        $this->assertSame('/api/v1/templates/t_1', $history[0]['request']->getUri()->getPath());
    }

    public function test_presets_maps_the_library_and_forwards_filters(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, [
                'data' => [[
                    'key' => 'order_shipped',
                    'name' => 'order_shipped',
                    'group' => 'commerce',
                    'group_label' => 'Commerce',
                    'category' => 'utility',
                    'title' => 'Order shipped',
                    'description' => 'Tell the customer the order is on its way.',
                    'languages' => ['ar', 'en'],
                    'variants' => [[
                        'language' => 'en',
                        'header_type' => 'TEXT',
                        'header_text' => 'Order update',
                        'body_text' => 'Hello {{1}}, order {{2}} has shipped today.',
                        'footer_text' => null,
                        'buttons' => [],
                        'example_variables' => ['Sara', '12345'],
                        'variable_count' => 2,
                    ]],
                ]],
            ]),
        ], $history);

        $presets = $client->templates()->presets(['category' => 'utility', 'group' => 'commerce', 'language' => 'en']);

        $this->assertCount(1, $presets);
        $this->assertInstanceOf(TemplatePreset::class, $presets[0]);
        $this->assertSame('order_shipped', $presets[0]->key);
        $this->assertSame('Commerce', $presets[0]->groupLabel);
        $this->assertSame(['ar', 'en'], $presets[0]->languages);
        $variant = $presets[0]->variant('en');
        $this->assertNotNull($variant);
        $this->assertSame(2, $variant->variableCount);
        $this->assertSame(['Sara', '12345'], $variant->exampleVariables);
        $this->assertNull($presets[0]->variant('fr'));

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/templates/presets', $request->getUri()->getPath());
        $this->assertStringContainsString('category=utility', $request->getUri()->getQuery());
        $this->assertStringContainsString('group=commerce', $request->getUri()->getQuery());
        $this->assertStringContainsString('language=en', $request->getUri()->getQuery());
    }

    public function test_create_from_preset_returns_template_and_submission_outcome(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, [
                'data' => ['id' => 't_1', 'name' => 'order_shipped', 'status' => 'pending', 'preset_key' => 'order_shipped', 'group' => 'Orders'],
                'meta' => ['submission' => ['submitted' => true, 'error' => null]],
            ]),
            ResponseFactory::json(201, [
                'data' => ['id' => 't_2', 'name' => 'order_shipped_2', 'status' => 'draft', 'preset_key' => 'order_shipped'],
                'meta' => ['submission' => ['submitted' => false, 'error' => 'no_template_capable_channel']],
            ]),
            ResponseFactory::json(201, [
                'data' => ['id' => 't_3', 'name' => 'order_shipped_3', 'status' => 'draft'],
                'meta' => ['submission' => null],
            ]),
        ], $history);

        $ok = $client->templates()->createFromPreset('order_shipped', [
            'language' => 'en',
            'channel_id' => 'ch_1',
            'group' => 'Orders',
            'submit' => true,
        ]);
        $this->assertSame('order_shipped', $ok->template->presetKey);
        $this->assertTrue($ok->submissionAttempted);
        $this->assertTrue($ok->submitted);
        $this->assertFalse($ok->submissionFailed());

        $failed = $client->templates()->createFromPreset('order_shipped', ['submit' => true]);
        $this->assertSame('order_shipped_2', $failed->template->name);
        $this->assertTrue($failed->submissionFailed());
        $this->assertSame('no_template_capable_channel', $failed->submissionError);

        $draft = $client->templates()->createFromPreset('order_shipped');
        $this->assertFalse($draft->submissionAttempted);
        $this->assertFalse($draft->submissionFailed());

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/templates/presets/order_shipped', $request->getUri()->getPath());
        $this->assertSame(
            ['language' => 'en', 'channel_id' => 'ch_1', 'group' => 'Orders', 'submit' => true],
            json_decode((string) $request->getBody(), true),
        );
    }

    public function test_create_from_unknown_preset_raises_not_found(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(404, ['message' => 'preset_not_found']),
        ]);

        $this->expectException(NotFoundException::class);
        $client->templates()->createFromPreset('no_such_preset');
    }
}
