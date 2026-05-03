<?php

namespace App\Services\AI;

use App\Models\StepRun;
use App\Models\ExecutionLog;

class PromptBuilder
{
    public function build(StepRun $stepRun): string
    {
        $logs = ExecutionLog::where('step_run_id', $stepRun->id)
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return "[{$log->level}] {$log->message} - " . json_encode($log->context);
            })
            ->join("\n");

        $stepType = 'unknown';
        if ($stepRun->workflowRun && $stepRun->workflowRun->workflowVersion) {
            $definition = $stepRun->workflowRun->workflowVersion->definition ?? clone $stepRun->workflowRun->workflowVersion->dag;
            $steps = collect($definition['steps'] ?? [])->keyBy('id');
            $stepType = $steps->get($stepRun->step_id)['type'] ?? 'unknown';
        }

        return <<<PROMPT
You are a senior DevOps engineer analyzing a workflow failure.
Analyze this step failure carefully.

Step ID: {$stepRun->step_id}
Step Type: {$stepType}
Attempt: {$stepRun->attempt}
Error Message: {$stepRun->last_error}

Recent Logs for this step:
{$logs}

Return a STRICT JSON response (and nothing else, no markdown wrappers) matching this schema:
{
  "root_cause": "brief explanation of why it failed",
  "suggestion": "actionable advice to fix it",
  "retry_safe": true or false,
  "severity": "low" or "medium" or "high"
}
PROMPT;
    }
}
