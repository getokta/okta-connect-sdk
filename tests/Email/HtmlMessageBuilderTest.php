<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Tests\Email;

use Okta\Connect\WhatsApp\Email\HtmlMessageBuilder;
use PHPUnit\Framework\TestCase;

final class HtmlMessageBuilderTest extends TestCase
{
    public function test_rtl_is_the_default_document_direction(): void
    {
        $html = HtmlMessageBuilder::make()->heading('مرحباً')->toHtml();

        $this->assertStringStartsWith('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html dir="rtl" lang="ar">', $html);
        $this->assertStringContainsString('text-align:right', $html);
        $this->assertStringContainsString('max-width:600px', $html);
        $this->assertStringContainsString('مرحباً', $html);
    }

    public function test_ltr_flips_direction_language_and_alignment(): void
    {
        $html = HtmlMessageBuilder::make(false)->paragraph('Hello')->toHtml();

        $this->assertStringContainsString('<html dir="ltr" lang="en">', $html);
        $this->assertStringContainsString('text-align:left', $html);
        $this->assertStringNotContainsString('dir="rtl"', $html);
    }

    public function test_user_text_is_html_escaped(): void
    {
        $html = HtmlMessageBuilder::make()
            ->heading('<script>alert(1)</script>')
            ->paragraph('Tom & "Jerry"')
            ->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('Tom &amp; &quot;Jerry&quot;', $html);
    }

    public function test_html_escape_hatch_passes_raw_markup_through(): void
    {
        $html = HtmlMessageBuilder::make()
            ->html('<table><tr><td>raw block</td></tr></table>')
            ->toHtml();

        $this->assertStringContainsString('<table><tr><td>raw block</td></tr></table>', $html);
    }

    public function test_button_renders_href_label_and_brand_color(): void
    {
        $html = HtmlMessageBuilder::make()
            ->brandColor('#123456')
            ->button('تتبع الطلب', 'https://acme.com/orders/1042?a=1&b=2')
            ->toHtml();

        $this->assertStringContainsString('href="https://acme.com/orders/1042?a=1&amp;b=2"', $html);
        $this->assertStringContainsString('تتبع الطلب', $html);
        $this->assertStringContainsString('bgcolor="#123456"', $html);
        $this->assertStringContainsString('background-color:#123456', $html);
        $this->assertStringContainsString('padding:12px 32px', $html);
    }

    public function test_brand_color_applies_even_when_set_after_the_button(): void
    {
        $html = HtmlMessageBuilder::make()
            ->button('Go', 'https://acme.com')
            ->brandColor('#654321')
            ->toHtml();

        $this->assertStringContainsString('bgcolor="#654321"', $html);
        $this->assertStringNotContainsString('#10b981', $html);
    }

    public function test_blocks_render_in_insertion_order(): void
    {
        $html = HtmlMessageBuilder::make()
            ->paragraph('first')
            ->heading('second')
            ->divider()
            ->paragraph('third')
            ->toHtml();

        $first = strpos($html, 'first');
        $second = strpos($html, 'second');
        $divider = strpos($html, 'border-top:1px solid');
        $third = strpos($html, 'third');

        $this->assertNotFalse($first);
        $this->assertNotFalse($second);
        $this->assertNotFalse($divider);
        $this->assertNotFalse($third);
        $this->assertLessThan($second, $first);
        $this->assertLessThan($divider, $second);
        $this->assertLessThan($third, $divider);
    }

    public function test_preheader_is_hidden_and_escaped(): void
    {
        $html = HtmlMessageBuilder::make()->preheader('Sneak <peek>')->toHtml();

        $this->assertStringContainsString('display:none', $html);
        $this->assertStringContainsString('Sneak &lt;peek&gt;', $html);
    }

    public function test_logo_spacer_image_and_footer_render_with_their_options(): void
    {
        $html = HtmlMessageBuilder::make()
            ->backgroundColor('#eef2ff')
            ->logo('https://cdn.acme.com/logo.png', 90)
            ->spacer(40)
            ->image('https://cdn.acme.com/hero.jpg', 'Hero')
            ->footer('© 2026 Acme')
            ->toHtml();

        $this->assertStringContainsString('src="https://cdn.acme.com/logo.png"', $html);
        $this->assertStringContainsString('width="90"', $html);
        $this->assertStringContainsString('height:40px', $html);
        $this->assertStringContainsString('src="https://cdn.acme.com/hero.jpg"', $html);
        $this->assertStringContainsString('alt="Hero"', $html);
        $this->assertStringContainsString('© 2026 Acme', $html);
        $this->assertStringContainsString('background-color:#eef2ff', $html);
    }

    public function test_to_string_aliases_to_html(): void
    {
        $builder = HtmlMessageBuilder::make()->heading('Same');

        $this->assertSame($builder->toHtml(), (string) $builder);
    }
}
