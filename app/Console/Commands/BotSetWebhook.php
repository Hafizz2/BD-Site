<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class BotSetWebhook extends Command
{
    protected $signature = 'bot:set-webhook {--url= : Override the webhook URL} {--delete : Remove the webhook instead}';

    protected $description = 'Register the Telegram webhook (with secret token) and the command menu';

    public function handle(Nutgram $bot): int
    {
        if ($this->option('delete')) {
            $bot->deleteWebhook();
            $this->info('Webhook removed.');

            return self::SUCCESS;
        }

        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '') {
            $this->error('Set TELEGRAM_WEBHOOK_SECRET in .env first (random string, letters/numbers/_/-).');

            return self::FAILURE;
        }

        $url = $this->option('url') ?: route('telegram.webhook');
        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram requires HTTPS. Got: {$url}");

            return self::FAILURE;
        }

        $bot->setWebhook(
            url: $url,
            secret_token: $secret,
            allowed_updates: ['message', 'callback_query', 'my_chat_member'],
            drop_pending_updates: false,
        );
        $bot->registerMyCommands();

        $this->info("Webhook set to {$url}");

        return self::SUCCESS;
    }
}
