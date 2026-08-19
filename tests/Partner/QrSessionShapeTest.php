<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Partner;

use Okta\Connect\WhatsApp\DTO\QrSession;
use PHPUnit\Framework\TestCase;

/**
 * The pairing payload, read from either generation's key names — and the
 * `error` field, which is what turns "stuck at pending" from a mystery into
 * a decision.
 */
final class QrSessionShapeTest extends TestCase
{
    public function test_it_reads_the_channel_envelope(): void
    {
        $session = QrSession::fromArray([
            'channel' => ['id' => 'ch_1', 'display_name' => 'Sales', 'status' => 'awaiting_scan'],
            'qr' => '2@abc',
            'qr_ttl_seconds' => 55,
            'error' => null,
        ]);

        $this->assertSame('ch_1', $session->id);
        $this->assertSame('Sales', $session->displayName);
        $this->assertSame('2@abc', $session->qr);
        $this->assertSame(55, $session->qrTtlSeconds);
        $this->assertFalse($session->hasError());
        $this->assertFalse($session->isTerminal());
    }

    public function test_it_reads_the_flat_shape_too(): void
    {
        $session = QrSession::fromArray([
            'id' => 'ch_2',
            'channel_id' => 'ch_2',
            'status' => 'connecting',
            'qr' => null,
            'expires_in' => 60,
        ]);

        $this->assertSame('ch_2', $session->id);
        $this->assertSame('connecting', $session->status);
        $this->assertSame(60, $session->qrTtlSeconds);
    }

    public function test_an_expired_code_is_terminal_so_the_poll_loop_ends(): void
    {
        $session = QrSession::fromArray([
            'id' => 'ch_3',
            'status' => 'qr_expired',
            'error' => 'qr_expired',
        ]);

        // Left out of the terminal set, a poll loop waits forever on a code
        // the gateway stopped regenerating.
        $this->assertTrue($session->isTerminal());
        $this->assertTrue($session->hasError());
        $this->assertFalse($session->isRetryable());
    }

    public function test_an_unreachable_gateway_is_the_one_error_worth_retrying(): void
    {
        $session = QrSession::fromArray([
            'id' => 'ch_4',
            'status' => 'pending',
            'error' => 'gateway_unavailable',
        ]);

        $this->assertTrue($session->isRetryable());
        $this->assertFalse($session->isTerminal());
    }
}
