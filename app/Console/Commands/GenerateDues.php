<?php

namespace App\Console\Commands;

use App\Services\Finance\DuesGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDues extends Command
{
    protected $signature = 'dues:generate {--month= : YYYY-MM (defaults to the current month)}';

    protected $description = 'Generate monthly dues from active fee plans (idempotent) and flag overdue dues';

    public function handle(DuesGenerator $generator): int
    {
        $month = $this->option('month') ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth() : now();
        $created = $generator->generateMonthly($month);
        $overdue = $generator->markOverdue();

        $this->info("Created {$created} dues for {$month->format('Y-m')}; flagged {$overdue} overdue.");

        return self::SUCCESS;
    }
}
