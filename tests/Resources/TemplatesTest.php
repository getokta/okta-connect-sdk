<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Message;
use Okta\Connect\WhatsApp\DTO\Template;
use Okta\Connect\WhatsApp\Enums\MessageType;
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
}
