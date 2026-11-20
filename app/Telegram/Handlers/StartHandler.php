<?php

namespace App\Telegram\Handlers;

use App\Services\Telegram\TelegramLinker;
use App\Settings\BotSettings;
use App\Telegram\BotContext;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;

class StartHandler
{
    public function __construct(private readonly TelegramLinker $linker, private readonly BotSettings $settings) {}

    /** /start <token> — deep link from the admin panel or registration thank-you page. */
    public function withToken(Nutgram $bot, string $token): void
    {
        if (! BotContext::isPrivate($bot)) {
            return;
        }

        $person = $this->linker->linkByToken(trim($token), (string) $bot->userId());

        if (! $person) {
            $bot->sendMessage(__('bot.link_invalid'));
            self::askContact($bot);

            return;
        }

        app()->setLocale(BotContext::locale($bot));
        $bot->sendMessage(
            text: __('bot.link_success', ['name' => e($person->full_name)])."\n\n".__('bot.help'),
            parse_mode: ParseMode::HTML,
            reply_markup: ReplyKeyboardRemove::make(true),
        );
    }

    /** /start — welcome, language choice, then contact sharing if not linked. */
    public function __invoke(Nutgram $bot): void
    {
        if (! BotContext::isPrivate($bot)) {
            return;
        }

        if (! BotContext::hasChosenLanguage($bot)) {
            self::askLanguage($bot);

            return;
        }

        self::welcome($bot, $this->settings);
    }

    public static function welcome(Nutgram $bot, BotSettings $settings): void
    {
        $welcome = app()->getLocale() === 'am' ? ($settings->welcome_message_am ?: $settings->welcome_message) : $settings->welcome_message;
        $person = BotContext::person($bot);

        if ($person) {
            $bot->sendMessage(text: e((string) $welcome)."\n\n".__('bot.help'), parse_mode: ParseMode::HTML);

            return;
        }

        $bot->sendMessage(text: e((string) $welcome));
        self::askContact($bot);
    }

    public static function askLanguage(Nutgram $bot): void
    {
        $bot->sendMessage(
            text: __('bot.choose_language'),
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('አማርኛ', callback_data: 'lang:am'),
                InlineKeyboardButton::make('English', callback_data: 'lang:en'),
            ),
        );
    }

    public static function askContact(Nutgram $bot): void
    {
        $bot->sendMessage(
            text: __('bot.share_contact_prompt'),
            reply_markup: ReplyKeyboardMarkup::make(resize_keyboard: true, one_time_keyboard: true)
                ->addRow(KeyboardButton::make(__('bot.share_contact_button'), request_contact: true)),
        );
    }
}
