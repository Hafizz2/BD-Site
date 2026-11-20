<?php

namespace App\Services\Telegram;

/**
 * Thin outbound Telegram API abstraction so notifications can be faked in tests.
 *
 * Buttons format: list of rows; each row is a list of ['text' => .., 'callback_data' => ..] or ['text' => .., 'url' => ..].
 */
interface TelegramGateway
{
    /**
     * @throws Exceptions\BotBlockedException
     * @throws Exceptions\RateLimitedException
     * @throws Exceptions\TelegramSendException
     */
    public function sendMessage(string $chatId, string $html, ?array $buttons = null): ?int;

    /** Send a local file as photo (or document if not an image) with caption. */
    public function sendFile(string $chatId, string $path, string $captionHtml, ?array $buttons = null): ?int;

    public function editMessage(string $chatId, int $messageId, string $html, bool $isCaption = false, ?array $buttons = null): void;

    public function answerCallback(string $callbackId, ?string $text = null, bool $alert = false): void;
}
