<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Models\Member;
use App\Models\Student;
use App\Telegram\BotContext;
use App\Telegram\Handlers\ContactHandler;
use App\Telegram\Handlers\InfoHandlers;
use App\Telegram\Handlers\LanguageHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Middleware\SetBotLocale;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Telegram bot — promotion & notifications only
|--------------------------------------------------------------------------
| People link their account so the jemea can reach them (due reminders,
| program invitations, announcements). Payments are recorded in the admin panel.
*/

$bot->middleware(SetBotLocale::class);

$bot->onCommand('start {token}', [StartHandler::class, 'withToken'])->description('Start / link your account');
$bot->onCommand('start', StartHandler::class);
$bot->onCommand('help', [InfoHandlers::class, 'help'])->description('What the bot can do');
$bot->onCommand('balance', [InfoHandlers::class, 'balance'])->description('What you owe and recent payments');
$bot->onCommand('announcements', [InfoHandlers::class, 'announcements'])->description('Latest announcements');
$bot->onCommand('mybatch', [InfoHandlers::class, 'myBatch'])->description('Your batch and schedule');
$bot->onCommand('language', LanguageHandler::class)->description('Change language / ቋንቋ ቀይር');

$bot->onContact(ContactHandler::class);

$bot->onCallbackQueryData('lang:{code}', [LanguageHandler::class, 'choose']);

// Any other text in a private chat → show help.
$bot->onText('{text}', function (Nutgram $bot, string $text) {
    if (! str_starts_with($text, '/') && BotContext::isPrivate($bot)) {
        $bot->sendMessage(text: __('bot.help'), parse_mode: 'HTML');
    }
});

// The user blocked / unblocked the bot.
$bot->onMyChatMember(function (Nutgram $bot) {
    $status = $bot->chatMember()?->new_chat_member?->status;
    $status = is_object($status) ? $status->value : $status;
    $chatId = (string) $bot->chatId();
    $blocked = $status === 'kicked' ? now() : null;
    Student::where('telegram_chat_id', $chatId)->update(['telegram_blocked_at' => $blocked]);
    Member::where('telegram_chat_id', $chatId)->update(['telegram_blocked_at' => $blocked]);
});

$bot->onException(function (Nutgram $bot, Throwable $e) {
    Log::error('Telegram bot error: '.$e->getMessage(), ['exception' => $e]);
    if (BotContext::isPrivate($bot)) {
        try {
            $bot->sendMessage('⚠️ Something went wrong. Please try again later.');
        } catch (Throwable) {
        }
    }
});
