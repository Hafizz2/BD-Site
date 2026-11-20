<?php

namespace App\Telegram\Handlers;

use App\Settings\BotSettings;
use App\Telegram\BotContext;
use SergiX44\Nutgram\Nutgram;

class LanguageHandler
{
    /** /language */
    public function __invoke(Nutgram $bot): void
    {
        StartHandler::askLanguage($bot);
    }

    /** Callback lang:{code} */
    public function choose(Nutgram $bot, string $code): void
    {
        BotContext::setLocale($bot, $code);
        $bot->answerCallbackQuery();
        $bot->sendMessage(__('bot.language_set'));

        if (BotContext::isPrivate($bot)) {
            StartHandler::welcome($bot, app(BotSettings::class));
        }
    }
}
