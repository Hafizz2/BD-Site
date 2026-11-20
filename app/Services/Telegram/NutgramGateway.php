<?php

namespace App\Services\Telegram;

use App\Services\Telegram\Exceptions\BotBlockedException;
use App\Services\Telegram\Exceptions\RateLimitedException;
use App\Services\Telegram\Exceptions\TelegramSendException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Throwable;

class NutgramGateway implements TelegramGateway
{
    public function __construct(private readonly Nutgram $bot) {}

    public function sendMessage(string $chatId, string $html, ?array $buttons = null): ?int
    {
        return $this->call(fn () => $this->bot->sendMessage(
            text: $html,
            chat_id: $chatId,
            parse_mode: ParseMode::HTML,
            reply_markup: $this->keyboard($buttons),
        )?->message_id);
    }

    public function sendFile(string $chatId, string $path, string $captionHtml, ?array $buttons = null): ?int
    {
        $isImage = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        $caption = mb_substr($captionHtml, 0, 1000);

        return $this->call(function () use ($chatId, $path, $caption, $buttons, $isImage) {
            $file = InputFile::make(fopen($path, 'r'), basename($path));

            return $isImage
                ? $this->bot->sendPhoto(photo: $file, chat_id: $chatId, caption: $caption, parse_mode: ParseMode::HTML, reply_markup: $this->keyboard($buttons))?->message_id
                : $this->bot->sendDocument(document: $file, chat_id: $chatId, caption: $caption, parse_mode: ParseMode::HTML, reply_markup: $this->keyboard($buttons))?->message_id;
        });
    }

    public function editMessage(string $chatId, int $messageId, string $html, bool $isCaption = false, ?array $buttons = null): void
    {
        $this->call(function () use ($chatId, $messageId, $html, $isCaption, $buttons) {
            $isCaption
                ? $this->bot->editMessageCaption(chat_id: $chatId, message_id: $messageId, caption: mb_substr($html, 0, 1000), parse_mode: ParseMode::HTML, reply_markup: $this->keyboard($buttons) ?? InlineKeyboardMarkup::make())
                : $this->bot->editMessageText(text: $html, chat_id: $chatId, message_id: $messageId, parse_mode: ParseMode::HTML, reply_markup: $this->keyboard($buttons) ?? InlineKeyboardMarkup::make());

            return null;
        });
    }

    public function answerCallback(string $callbackId, ?string $text = null, bool $alert = false): void
    {
        $this->call(fn () => $this->bot->answerCallbackQuery(callback_query_id: $callbackId, text: $text, show_alert: $alert));
    }

    private function keyboard(?array $rows): ?InlineKeyboardMarkup
    {
        if (! $rows) {
            return null;
        }
        $kb = InlineKeyboardMarkup::make();
        foreach ($rows as $row) {
            $buttons = array_map(fn (array $b) => isset($b['url'])
                ? InlineKeyboardButton::make($b['text'], url: $b['url'])
                : InlineKeyboardButton::make($b['text'], callback_data: $b['callback_data']), $row);
            $kb->addRow(...$buttons);
        }

        return $kb;
    }

    private function call(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            throw self::translate($e);
        }
    }

    public static function translate(Throwable $e): TelegramSendException
    {
        $code = (int) $e->getCode();
        $msg = $e->getMessage();

        if ($code === 429 || str_contains($msg, 'Too Many Requests')) {
            $retry = 5;
            if (method_exists($e, 'getParameter')) {
                $retry = (int) ($e->getParameter('retry_after') ?? $retry);
            }
            if (preg_match('/retry after (\d+)/i', $msg, $m)) {
                $retry = (int) $m[1];
            }

            return new RateLimitedException(max(1, $retry), $msg);
        }

        if ($code === 403 || str_contains($msg, 'bot was blocked') || str_contains($msg, 'user is deactivated') || str_contains($msg, 'chat not found')) {
            return new BotBlockedException($msg, 403, $e);
        }

        return new TelegramSendException($msg, $code, $e);
    }
}
