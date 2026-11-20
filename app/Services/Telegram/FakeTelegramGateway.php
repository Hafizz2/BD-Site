<?php

namespace App\Services\Telegram;

use App\Services\Telegram\Exceptions\BotBlockedException;

/**
 * In-memory gateway used in tests and when no bot token is configured.
 */
class FakeTelegramGateway implements TelegramGateway
{
    /** @var list<array{type:string,chat_id:string,text:string,buttons:?array,path?:string}> */
    public array $sent = [];

    public array $edited = [];

    public array $callbacks = [];

    /** @var list<string> chat ids that behave as if they blocked the bot */
    public array $blocked = [];

    private int $nextId = 1000;

    public function sendMessage(string $chatId, string $html, ?array $buttons = null): ?int
    {
        $this->guard($chatId);
        $this->sent[] = ['type' => 'message', 'chat_id' => $chatId, 'text' => $html, 'buttons' => $buttons];

        return $this->nextId++;
    }

    public function sendFile(string $chatId, string $path, string $captionHtml, ?array $buttons = null): ?int
    {
        $this->guard($chatId);
        $this->sent[] = ['type' => 'file', 'chat_id' => $chatId, 'text' => $captionHtml, 'buttons' => $buttons, 'path' => $path];

        return $this->nextId++;
    }

    public function editMessage(string $chatId, int $messageId, string $html, bool $isCaption = false, ?array $buttons = null): void
    {
        $this->edited[] = compact('chatId', 'messageId', 'html', 'isCaption', 'buttons');
    }

    public function answerCallback(string $callbackId, ?string $text = null, bool $alert = false): void
    {
        $this->callbacks[] = compact('callbackId', 'text', 'alert');
    }

    public function sentTo(string $chatId): array
    {
        return array_values(array_filter($this->sent, fn ($m) => $m['chat_id'] === $chatId));
    }

    private function guard(string $chatId): void
    {
        if (in_array($chatId, $this->blocked, true)) {
            throw new BotBlockedException('Forbidden: bot was blocked by the user', 403);
        }
    }
}
