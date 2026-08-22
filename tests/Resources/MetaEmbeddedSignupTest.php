<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Resources;

use Okta\Connect\WhatsApp\Tests\Fixtures\ResponseFactory;
use PHPUnit\Framework\TestCase;

/**
 * The WABA id is delivered to the browser once, via postMessage, with no
 * second chance. Finalising must therefore work with the code alone — the
 * platform derives the id from the exchanged token when it is omitted.
 */
final class MetaEmbeddedSignupTest extends TestCase
{
    public function test_finalises_with_the_code_alone(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['channels' => [['id' => 'ch_1', 'display_name' => 'خط المبيعات']]]),
        ], $history);

        $channels = $client->meta()->completeEmbeddedSignup('CODE_FROM_FB');

        $this->assertCount(1, $channels);

        // An explicit empty waba_id, not an absent key: the platform treats
        // empty as "derive it from the token".
        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('CODE_FROM_FB', $body['code']);
        $this->assertSame('', $body['waba_id']);
    }

    public function test_still_passes_a_supplied_waba_id_through(): void
    {
        $history = [];
        $client = ResponseFactory::makeClient([
            ResponseFactory::json(201, ['channels' => [['id' => 'ch_1']]]),
        ], $history);

        $client->meta()->completeEmbeddedSignup('CODE_FROM_FB', 'WABA_777');

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('WABA_777', $body['waba_id']);
    }
}
