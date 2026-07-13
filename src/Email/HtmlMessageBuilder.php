<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Email;

use Stringable;

/**
 * Fluent builder for email-client-safe HTML messages.
 *
 * Produces a complete document (table-based layout, every style inlined,
 * 600px centered card) that renders correctly in Gmail, Outlook and the
 * other lowest-common-denominator clients — so callers can design a
 * branded message without hand-writing email markup:
 *
 *   $html = HtmlMessageBuilder::make()               // RTL by default
 *       ->brandColor('#10b981')
 *       ->logo('https://cdn.acme.com/logo.png')
 *       ->preheader('طلبك في الطريق')
 *       ->heading('شكراً لطلبك!')
 *       ->paragraph('طلبك رقم 1042 قيد التجهيز الآن.')
 *       ->button('تتبع الطلب', 'https://acme.com/orders/1042')
 *       ->footer('© 2026 Acme — جميع الحقوق محفوظة')
 *       ->toHtml();
 *
 * Content blocks render in insertion order. All user-supplied text is
 * HTML-escaped except the `html()` escape hatch. RTL-first: `make()`
 * defaults to dir="rtl" / lang="ar"; pass `false` for LTR.
 */
final class HtmlMessageBuilder implements Stringable
{
    private const FONT_STACK = "'Segoe UI',Tahoma,Helvetica,Arial,sans-serif";

    private string $brandColor = '#10b981';

    private string $backgroundColor = '#f8fafc';

    private ?string $logoUrl = null;

    private int $logoWidth = 120;

    private ?string $preheader = null;

    private ?string $footer = null;

    /**
     * Content blocks in insertion order. Each entry is a spec array whose
     * first element is the block type; rendering is deferred to toHtml()
     * so styling setters may be called before or after adding blocks.
     *
     * @var list<array{0: string, 1?: string, 2?: string|int|null}>
     */
    private array $blocks = [];

    private function __construct(
        private readonly bool $rtl,
    ) {
    }

    /**
     * Start a new message. RTL by default (Arabic-first platform) —
     * pass `false` for a left-to-right document.
     */
    public static function make(bool $rtl = true): self
    {
        return new self($rtl);
    }

    /**
     * Accent colour for buttons (hex, e.g. `#10b981`).
     */
    public function brandColor(string $hex): self
    {
        $this->brandColor = $hex;

        return $this;
    }

    /**
     * Page background behind the white card (hex, e.g. `#f8fafc`).
     */
    public function backgroundColor(string $hex): self
    {
        $this->backgroundColor = $hex;

        return $this;
    }

    /**
     * Brand logo, centred at the top of the card.
     */
    public function logo(string $url, int $width = 120): self
    {
        $this->logoUrl = $url;
        $this->logoWidth = $width;

        return $this;
    }

    /**
     * Hidden preview text shown next to the subject in inbox lists —
     * never visible in the opened message.
     */
    public function preheader(string $text): self
    {
        $this->preheader = $text;

        return $this;
    }

    public function heading(string $text): self
    {
        $this->blocks[] = ['heading', $text];

        return $this;
    }

    public function paragraph(string $text): self
    {
        $this->blocks[] = ['paragraph', $text];

        return $this;
    }

    /**
     * Solid brand-colour pill button — a bulletproof `<a>` with inline
     * padding, so it stays clickable across its full face in Outlook.
     */
    public function button(string $label, string $url): self
    {
        $this->blocks[] = ['button', $label, $url];

        return $this;
    }

    public function divider(): self
    {
        $this->blocks[] = ['divider'];

        return $this;
    }

    public function spacer(int $px = 16): self
    {
        $this->blocks[] = ['spacer', (string) $px];

        return $this;
    }

    public function image(string $url, ?string $alt = null): self
    {
        $this->blocks[] = ['image', $url, $alt];

        return $this;
    }

    /**
     * Escape hatch: append a raw, pre-built HTML block verbatim.
     * The ONLY entry point that bypasses escaping — never pass
     * untrusted input here.
     */
    public function html(string $rawBlock): self
    {
        $this->blocks[] = ['html', $rawBlock];

        return $this;
    }

    /**
     * Muted small print rendered below the card (unsubscribe hints,
     * address, copyright).
     */
    public function footer(string $text): self
    {
        $this->footer = $text;

        return $this;
    }

    /**
     * Render the complete `<!DOCTYPE html>` document.
     */
    public function toHtml(): string
    {
        $dir = $this->rtl ? 'rtl' : 'ltr';
        $lang = $this->rtl ? 'ar' : 'en';
        $align = $this->rtl ? 'right' : 'left';
        $font = self::FONT_STACK;
        $background = $this->e($this->backgroundColor);

        $lines = [];
        $lines[] = '<!DOCTYPE html>';
        $lines[] = '<html dir="'.$dir.'" lang="'.$lang.'">';
        $lines[] = '<head>';
        $lines[] = '<meta charset="utf-8">';
        $lines[] = '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $lines[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
        $lines[] = '<title></title>';
        $lines[] = '</head>';
        $lines[] = '<body style="margin:0;padding:0;word-spacing:normal;background-color:'.$background.';">';

        if ($this->preheader !== null) {
            $lines[] = '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">'
                .$this->e($this->preheader)
                .str_repeat('&nbsp;&zwnj;', 20)
                .'</div>';
        }

        $lines[] = '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:'.$background.';">';
        $lines[] = '<tr>';
        $lines[] = '<td align="center" style="padding:24px 12px;">';
        $lines[] = '<table role="presentation" width="600" border="0" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:8px;">';

        if ($this->logoUrl !== null) {
            $lines[] = '<tr>';
            $lines[] = '<td align="center" style="padding:32px 32px 0;">';
            $lines[] = '<img src="'.$this->e($this->logoUrl).'" alt="" width="'.$this->logoWidth.'" style="display:block;border:0;max-width:100%;height:auto;">';
            $lines[] = '</td>';
            $lines[] = '</tr>';
        }

        $lines[] = '<tr>';
        $lines[] = '<td dir="'.$dir.'" style="padding:32px;font-family:'.$font.';text-align:'.$align.';">';

        foreach ($this->blocks as $block) {
            $lines[] = $this->renderBlock($block, $align, $font);
        }

        $lines[] = '</td>';
        $lines[] = '</tr>';
        $lines[] = '</table>';

        if ($this->footer !== null) {
            $lines[] = '<table role="presentation" width="600" border="0" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;">';
            $lines[] = '<tr>';
            $lines[] = '<td dir="'.$dir.'" align="center" style="padding:24px 32px 0;font-family:'.$font.';font-size:12px;line-height:18px;color:#94a3b8;text-align:center;">'.$this->e($this->footer).'</td>';
            $lines[] = '</tr>';
            $lines[] = '</table>';
        }

        $lines[] = '</td>';
        $lines[] = '</tr>';
        $lines[] = '</table>';
        $lines[] = '</body>';
        $lines[] = '</html>';

        return implode("\n", $lines);
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * @param  array{0: string, 1?: string, 2?: string|int|null}  $block
     */
    private function renderBlock(array $block, string $align, string $font): string
    {
        $brand = $this->e($this->brandColor);

        return match ($block[0]) {
            'heading' => '<h1 style="margin:0 0 16px;font-family:'.$font.';font-size:24px;line-height:32px;font-weight:bold;color:#0f172a;">'.$this->e((string) ($block[1] ?? '')).'</h1>',
            'paragraph' => '<p style="margin:0 0 16px;font-family:'.$font.';font-size:16px;line-height:26px;color:#334155;">'.$this->e((string) ($block[1] ?? '')).'</p>',
            'button' => '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">'
                .'<tr><td align="'.$align.'">'
                .'<table role="presentation" border="0" cellpadding="0" cellspacing="0">'
                .'<tr><td align="center" bgcolor="'.$brand.'" style="border-radius:9999px;background-color:'.$brand.';">'
                .'<a href="'.$this->e((string) ($block[2] ?? '')).'" target="_blank" style="display:inline-block;padding:12px 32px;font-family:'.$font.';font-size:16px;font-weight:bold;line-height:20px;color:#ffffff;text-decoration:none;border-radius:9999px;">'.$this->e((string) ($block[1] ?? '')).'</a>'
                .'</td></tr>'
                .'</table>'
                .'</td></tr>'
                .'</table>',
            'divider' => '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:16px 0;">'
                .'<tr><td style="border-top:1px solid #e2e8f0;font-size:1px;line-height:1px;">&nbsp;</td></tr>'
                .'</table>',
            'spacer' => '<div style="height:'.(int) ($block[1] ?? 16).'px;line-height:'.(int) ($block[1] ?? 16).'px;font-size:1px;">&nbsp;</div>',
            'image' => '<img src="'.$this->e((string) ($block[1] ?? '')).'" alt="'.$this->e((string) ($block[2] ?? '')).'" style="display:block;width:100%;max-width:100%;height:auto;border:0;border-radius:6px;margin:0 0 16px;">',
            'html' => (string) ($block[1] ?? ''),
            default => '',
        };
    }

    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
