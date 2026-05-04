<?php

namespace App\Services;

use App\Repositories\CronTriggerRepository;
use Cron\CronExpression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CronTriggerService
{
    public function __construct(
        private readonly CronTriggerRepository $cronTriggerRepository,
        private readonly ExecutionService $executionService,
    ) {
    }

    public function runDueTriggers(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $windowStart = $now->copy()->startOfMinute();
        $checked = 0;
        $triggered = 0;
        $skipped = 0;

        $triggers = $this->cronTriggerRepository->listEnabledCronTriggers();

        foreach ($triggers as $trigger) {
            $checked++;
            $workflow = $trigger->workflow;
            $expression = data_get($trigger->config, 'cron_expression');
            $timezone = data_get($trigger->config, 'cron_timezone') ?: config('app.timezone');

            if (! $workflow || ! $workflow->latestVersion || empty($expression)) {
                $skipped++;
                continue;
            }

            try {
                $cron = CronExpression::factory($expression);
            } catch (\Throwable $exception) {
                Log::warning('Invalid cron expression for trigger.', [
                    'trigger_id' => $trigger->id,
                    'expression' => $expression,
                    'error' => $exception->getMessage(),
                ]);
                $skipped++;
                continue;
            }

            $nowInTz = $now->copy()->setTimezone($timezone);
            if (! $cron->isDue($nowInTz)) {
                $skipped++;
                continue;
            }

            if (! $this->cronTriggerRepository->markTriggered($trigger->id, $windowStart, $now)) {
                $skipped++;
                continue;
            }

            $this->executionService->startWorkflow($workflow->id, []);
            $triggered++;
        }

        return [
            'checked' => $checked,
            'triggered' => $triggered,
            'skipped' => $skipped,
        ];
    }
}
