<?php

namespace App\Services;

use App\Jobs\RunStepJob;
use App\Models\StepRun;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Repositories\WorkflowRepository;
use Illuminate\Support\Facades\DB;

class ExecutionService
{
    /**
     * Initialize a workflow run: create step_runs and dispatch root steps.
     */
    public function initializeRun(WorkflowRun $workflowRun, WorkflowVersion $version, array $dag, array $input = []): void
    {
        DB::transaction(function () use ($workflowRun, $version, $dag, $input) {
            $steps = $dag['definition']['steps'] ?? $dag['steps'] ?? [];

            foreach ($steps as $step) {
                StepRun::create([
                    'workflow_run_id' => $workflowRun->id,
                    'step_id' => $step['id'],
                    'attempt' => 0,
                    'status' => 'pending',
                    'input' => $input,
                ]);
            }
        });

        // dispatch root steps
        $rootSteps = $dag['root_steps'] ?? [];

        foreach ($rootSteps as $stepId) {
            $stepRun = StepRun::where('workflow_run_id', $workflowRun->id)
                ->where('step_id', $stepId)
                ->first();

            if ($stepRun) {
                RunStepJob::dispatch($stepRun->id);
            }
        }
    }

    /**
     * Start a workflow by id: load version, create run, initialize and dispatch root steps.
     */
    public function startWorkflow(string $workflowId, array $input = []): WorkflowRun
    {
        $repo = new WorkflowRepository();

        $workflow = $repo->findWithLatestVersion($workflowId);

        if (! $workflow || ! $workflow->latestVersion) {
            throw new \RuntimeException('Workflow or latest version not found');
        }

        $version = $workflow->latestVersion;

        $dag = (new \App\Services\Workflow\DagParser())->parse($version->definition ?? $version->dag);

        $run = $repo->createRun($workflow, $version, $input);

        $this->initializeRun($run, $version, $dag, $input);

        return $run;
    }

    /**
     * Execute a step run by id. Handles dependency checks, execution and dispatching next steps.
     */
    public function executeStepRun(string $stepRunId): void
    {
        // default max attempts
        $this->executeStepAttempt($stepRunId, 3);
    }

    /**
     * Execute a single step attempt with retry handling.
     */
    public function executeStepAttempt(string $stepRunId, int $maxAttempts): void
    {
        $stepRun = StepRun::findOrFail($stepRunId);

        $workflowRun = $stepRun->workflowRun;

        if ($this->hasUnfinishedDependencies($workflowRun, $stepRun)) {
            // caller should release the job for retry
            throw new \RuntimeException('Unfinished dependencies');
        }

        // increment attempt atomically
        $stepRun->increment('attempt');
        $stepRun->refresh();

        $stepRun->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // broadcast step started
        event(new \App\Events\StepStarted($stepRun));

        try {
            $definition = $this->getStepDefinition($workflowRun, $stepRun->step_id);

            $output = \App\Services\Execution\StepExecutorFactory::execute($definition, $stepRun->input ?? []);

            $stepRun->update([
                'status' => 'success',
                'output' => $output,
                'finished_at' => now(),
            ]);

            // broadcast success
            event(new \App\Events\StepSucceeded($stepRun));

            $this->dispatchNextSteps($workflowRun, $stepRun);

        } catch (\Throwable $e) {
            // record error
            $stepRun->update([
                'last_error' => (string) $e->getMessage(),
            ]);

            $retrySvc = new \App\Services\Execution\RetryService();

            if (! $retrySvc->isRetryable($e)) {
                $stepRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                ]);

                // broadcast failure
                event(new \App\Events\StepFailed($stepRun, $e->getMessage()));

                return;
            }

            if ($stepRun->attempt >= $maxAttempts) {
                $stepRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                ]);

                // broadcast failure when retry limit is reached
                event(new \App\Events\StepFailed($stepRun, $e->getMessage()));

                return;
            }

            // rethrow to allow queue retry/backoff
            throw $e;
        }
    }

    /**
     * Check if a step run has unfinished dependencies.
     */
    public function hasUnfinishedDependencies(WorkflowRun $workflowRun, StepRun $stepRun): bool
    {
        $version = $workflowRun->workflowVersion;

        $definition = $version->definition ?? $version->dag;

        $steps = collect($definition['steps'] ?? [])->keyBy('id');

        $step = $steps->get($stepRun->step_id);

        if (! $step) {
            return false;
        }

        $dependsOn = $step['depends_on'] ?? [];

        if (empty($dependsOn)) {
            return false;
        }

        $exists = StepRun::where('workflow_run_id', $workflowRun->id)
            ->whereIn('step_id', $dependsOn)
            ->where('status', '!=', 'success')
            ->exists();

        return $exists;
    }

    public function getStepDefinition(WorkflowRun $workflowRun, string $stepId): array
    {
        $version = $workflowRun->workflowVersion;

        $definition = $version->definition ?? $version->dag;

        $steps = collect($definition['steps'] ?? [])->keyBy('id');

        return $steps->get($stepId, []);
    }

    /**
     * Dispatch next executable steps after a step completes.
     */
    public function dispatchNextSteps(WorkflowRun $workflowRun, StepRun $completedStepRun): void
    {
        $version = $workflowRun->workflowVersion;

        $definition = $version->definition ?? $version->dag;

        $completed = StepRun::where('workflow_run_id', $workflowRun->id)
            ->where('status', 'success')
            ->pluck('step_id')
            ->map(fn ($v) => (string) $v)
            ->toArray();

        $parser = new \App\Services\Workflow\DagParser();

        $next = $parser->getNextExecutableSteps($definition, $completed);

        foreach ($next as $stepId) {
            $stepRun = StepRun::where('workflow_run_id', $workflowRun->id)
                ->where('step_id', $stepId)
                ->first();

            if ($stepRun && $stepRun->status === 'pending') {
                RunStepJob::dispatch($stepRun->id);
            }
        }

        // Check whether the workflow is completed (no pending/running steps)
        $unfinished = StepRun::where('workflow_run_id', $workflowRun->id)
            ->whereIn('status', ['pending', 'running'])
            ->exists();

        if (! $unfinished) {
            // mark workflow run completed if not already
            $workflowRun->refresh();

            if ($workflowRun->status !== 'completed') {
                $workflowRun->update(['status' => 'completed']);
                event(new \App\Events\WorkflowCompleted($workflowRun));
            }
        }
    }
}
