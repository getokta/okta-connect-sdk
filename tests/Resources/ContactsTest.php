<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\DTO\Contact;
use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ContactsTest extends TestCase
{
    public function test_list_filters_pass_through_as_query_string(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => [['id' => 'co_1', 'phone' => '+9665']]]),
        ], $history);

        $page = $client->contacts()->list(['search' => '+966']);

        $this->assertCount(1, $page);
        $this->assertStringContainsString('search=', $history[0]['request']->getUri()->getQuery());
    }

    public function test_upsert_returns_contact_dto(): void
    {
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(200, ['data' => ['id' => 'co_1', 'phone' => '+9665', 'name' => 'Ali']]),
        ]);

        $contact = $client->contacts()->upsert(['phone' => '+9665', 'name' => 'Ali']);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame('Ali', $contact->name);
    }
}
