<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Polling;

class BotPoll extends Command
{
    protected $signature = 'bot:poll';

    protected $description = 'Run the Telegram bot with long polling (local development)';

    public function handle(Nutgram $bot): int
    {
        $bot->deleteWebhook();
        $bot->registerMyCommands();
        $this->info('Polling Telegram… press Ctrl+C to stop.');
        $bot->setRunningMode(Polling::class);
        $bot->run();

        return self::SUCCESS;
    }
}
