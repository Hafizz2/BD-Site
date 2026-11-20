<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SergiX44\Nutgram\Nutgram;

/**
 * Telegram webhook. Protected by the secret token Telegram echoes in
 * the X-Telegram-Bot-Api-Secret-Token header (set via `php artisan bot:set-webhook`).
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, Nutgram $bot): Response
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $bot->run();

        return response('', 200);
    }
}
