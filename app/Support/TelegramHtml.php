<?php

namespace App\Support;

/**
 * Converts rich-editor HTML into the small HTML subset Telegram accepts
 * (b, i, u, s, a, code, pre, blockquote).
 */
final class TelegramHtml
{
    public static function fromRich(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        $replacements = [
            '/<\s*br\s*\/?>/i' => "\n",
            '/<\s*\/p\s*>/i' => "\n\n",
            '/<\s*p[^>]*>/i' => '',
            '/<\s*h[1-6][^>]*>/i' => '<b>',
            '/<\s*\/h[1-6]\s*>/i' => "</b>\n\n",
            '/<\s*li[^>]*>/i' => '• ',
            '/<\s*\/li\s*>/i' => "\n",
            '/<\s*\/?(ul|ol)[^>]*>/i' => "\n",
            '/<\s*strong[^>]*>/i' => '<b>',
            '/<\s*\/strong\s*>/i' => '</b>',
            '/<\s*em[^>]*>/i' => '<i>',
            '/<\s*\/em\s*>/i' => '</i>',
            '/<\s*del[^>]*>/i' => '<s>',
            '/<\s*\/del\s*>/i' => '</s>',
            '/<\s*strike[^>]*>/i' => '<s>',
            '/<\s*\/strike\s*>/i' => '</s>',
            '/<\s*hr[^>]*>/i' => "\n———\n",
        ];
        $html = preg_replace(array_keys($replacements), array_values($replacements), $html) ?? $html;

        // Keep only Telegram-safe tags; strip attributes except a[href].
        $html = strip_tags($html, '<b><i><u><s><a><code><pre><blockquote>');
        $html = preg_replace_callback('/<a\s[^>]*href\s*=\s*("|\')(.*?)\1[^>]*>/i', fn ($m) => '<a href="'.htmlspecialchars(html_entity_decode($m[2]), ENT_QUOTES).'">', $html) ?? $html;
        $html = preg_replace('/<(b|i|u|s|code|pre|blockquote)\s[^>]*>/i', '<$1>', $html) ?? $html;

        // Normalise entities Telegram doesn't know.
        $html = str_replace('&nbsp;', ' ', $html);
        $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;

        return trim($html);
    }

    /** Plain-text excerpt for previews/website. */
    public static function excerpt(?string $html, int $limit = 160): string
    {
        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?? ''), $limit);
    }
}
