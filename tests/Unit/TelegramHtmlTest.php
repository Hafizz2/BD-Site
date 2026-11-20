<?php

use App\Support\TelegramHtml;

it('converts rich editor HTML to Telegram-safe HTML', function () {
    $html = '<h2>Notice</h2><p>Class is <strong>moved</strong> to <em>Sunday</em>.</p><ul><li>Bring notes</li><li>Be on time</li></ul><p><a href="https://example.com" target="_blank" class="x">Details</a><script>alert(1)</script></p>';

    $out = TelegramHtml::fromRich($html);

    expect($out)->toContain('<b>Notice</b>')
        ->toContain('<b>moved</b>')
        ->toContain('<i>Sunday</i>')
        ->toContain('• Bring notes')
        ->toContain('<a href="https://example.com">Details</a>')
        ->not->toContain('<p>')
        ->not->toContain('<script>')
        ->not->toContain('class=');
});
