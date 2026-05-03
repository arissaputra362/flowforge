<?php

namespace App\Services;

use App\Models\StepRun;
use App\Repositories\ExecutionLogRepository;

class LogService
{
    private ExecutionLogRepository $repository;

    public function __construct(?ExecutionLogRepository $repository = null)
    {
        $this->repository = $repository ?? new ExecutionLogRepository();
    }

    public function info(StepRun $stepRun, string $message, array $context = [])
    {
        $this->log('info', $stepRun, $message, $context);
    }

    public function warning(StepRun $stepRun, string $message, array $context = [])
    {
        $this->log('warning', $stepRun, $message, $context);
    }

    public function error(StepRun $stepRun, string $message, array $context = [])
    {
        $this->log('error', $stepRun, $message, $context);
    }

    protected function log(string $level, StepRun $stepRun, string $message, array $context)
    {
        // Inject correlation_id to assist with distributed tracing or log aggregation
        if (!isset($context['correlation_id'])) {
            $context['correlation_id'] = $stepRun->workflow_run_id;
        }

        // Attach step context dynamically
        $context = array_merge([
            'step_id' => $stepRun->step_id,
            'attempt' => $stepRun->attempt,
        ], $context);

        $this->repository->create([
            'workflow_run_id' => $stepRun->workflow_run_id,
            'step_run_id' => $stepRun->id,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
