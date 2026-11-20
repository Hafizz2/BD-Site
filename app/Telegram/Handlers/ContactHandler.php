<?php

namespace App\Telegram\Handlers;

use App\Services\Telegram\TelegramLinker;
use App\Telegram\BotContext;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;

class ContactHandler
{
    public function __construct(private readonly TelegramLinker $linker) {}

    public function __invoke(Nutgram $bot): void
    {
        if (! BotContext::isPrivate($bot)) {
            return;
        }

        $contact = $bot->message()?->contact;
        if (! $contact) {
            return;
        }

        // Only accept the user's own contact (not a forwarded one).
        if ((string) $contact->user_id !== (string) $bot->userId()) {
            $bot->sendMessage(__('bot.contact_not_own'));

            return;
        }

        $linked = $this->linker->linkByPhone($contact->phone_number, (string) $bot->userId());

        if (! $linked) {
            $bot->sendMessage(text: __('bot.contact_not_found'), reply_markup: ReplyKeyboardRemove::make(true));

            return;
        }

        // Persist the language chosen before linking.
        BotContext::setLocale($bot, BotContext::locale($bot));

        $bot->sendMessage(
            text: __('bot.link_success', ['name' => e($linked[0]->full_name)])."\n\n".__('bot.help'),
            parse_mode: ParseMode::HTML,
            reply_markup: ReplyKeyboardRemove::make(true),
        );
    }
}
