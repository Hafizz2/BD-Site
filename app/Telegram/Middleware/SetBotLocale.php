<?php

namespace App\Telegram\Middleware;

use App\Telegram\BotContext;
use SergiX44\Nutgram\Nutgram;

class SetBotLocale
{
    public function __invoke(Nutgram $bot, $next): void
    {
        app()->setLocale(BotContext::locale($bot));
        $next($bot);
    }
}
