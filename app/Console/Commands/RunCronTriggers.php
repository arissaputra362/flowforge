<?php

namespace App\Console\Commands;

use App\Services\CronTriggerService;
use Illuminate\Console\Command;

class RunCronTriggers extends Command
{
    protected $signature = 'workflows:run-cron';

    protected $description = 'Trigger workflows scheduled by cron triggers.';

    public function handle(CronTriggerService $cronTriggerService): int
    {
        $summary = $cronTriggerService->runDueTriggers();

        $this->info(sprintf(
            'Cron triggers checked: %d, triggered: %d, skipped: %d',
            $summary['checked'],
            $summary['triggered'],
            $summary['skipped']
        ));

        return self::SUCCESS;
    }
}
