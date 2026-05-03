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
        if (!in_array($stepRun->status, ['queued', 'pending'])) {
            return;
        }

        $workflowRun = $stepRun->workflowRun;
        $logger = app(\App\Services\LogService::class);

        if ($this->hasUnfinishedDependencies($workflowRun, $stepRun)) {
            // caller should release the job for retry
            throw new \RuntimeException('Unfinished dependencies');
        }

        // increment attempt atomically
        $stepRun->increment('attempt');
        $stepRun->refresh();

        $logger->info($stepRun, 'Step started');

        $stepRun->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // broadcast step started
        event(new \App\Events\StepStarted($stepRun));

        try {
            $definition = $this->getStepDefinition($workflowRun, $stepRun->step_id);

            $context = $this->buildStepContext($workflowRun, $stepRun);

            $output = \App\Services\Execution\StepExecutorFactory::execute($definition, $context);

            $logger->info($stepRun, 'Step succeeded', ['output' => $output]);

            $stepRun->update([
                'status' => 'success',
                'output' => $output,
                'finished_at' => now(),
            ]);

            // broadcast success
            event(new \App\Events\StepSucceeded($stepRun));

            $this->dispatchNextSteps($workflowRun, $stepRun);

        } catch (\Throwable $e) {
            $logger->error($stepRun, 'Step failed', ['error' => $e->getMessage()]);

            // record error
            $stepRun->update([
                'last_error' => (string) $e->getMessage(),
            ]);

            $retrySvc = new \App\Services\Execution\RetryService();

            if (! $retrySvc->isRetryable($e)) {
                $aiSvc = app(\App\Services\AI\FailureAnalyzerService::class);
                $analysis = $aiSvc->analyze($stepRun);

                $stepRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'ai_analysis' => $analysis,
                ]);

                // broadcast failure
                event(new \App\Events\StepFailed($stepRun, $e->getMessage()));

                return;
            }

            if ($stepRun->attempt >= $maxAttempts) {
                $aiSvc = app(\App\Services\AI\FailureAnalyzerService::class);
                $analysis = $aiSvc->analyze($stepRun);

                $stepRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'ai_analysis' => $analysis,
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
     * Build context untuk executor: initial input + semua output step yang sudah success.
     */
    private function buildStepContext(WorkflowRun $workflowRun, StepRun $currentStepRun): array
    {
        $completedSteps = StepRun::where('workflow_run_id', $workflowRun->id)
            ->where('status', 'success')
            ->whereNotNull('output')
            ->get(['step_id', 'output']);

        $stepsOutput = [];

        foreach ($completedSteps as $step) {
            $stepsOutput[(string) $step->step_id] = [
                'output' => $step->output,
            ];
        }

        return [
            'input' => $currentStepRun->input ?? [],
            'steps' => $stepsOutput,
        ];
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
            ->whereNotIn('status', ['success', 'skipped'])  // queued/running/pending = belum selesai
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
        $version     = $workflowRun->workflowVersion;
        $definition  = $version->definition ?? $version->dag;
        $stepDef     = $this->getStepDefinition($workflowRun, $completedStepRun->step_id);

        // --- BRANCH ROUTING untuk condition step ---
        if (($stepDef['type'] ?? null) === 'condition') {
            $this->handleConditionBranch($workflowRun, $completedStepRun, $stepDef, $definition);
        } else {
            // --- NORMAL: dispatch berdasarkan depends_on ---
            $this->dispatchByDependencies($workflowRun, $definition);
        }

        $this->checkAndCompleteWorkflow($workflowRun);
    }

    /**
     * Handle branch routing setelah condition step selesai.
     */
    private function handleConditionBranch(
        WorkflowRun $workflowRun,
        StepRun $completedStepRun,
        array $stepDef,
        array $definition
    ): void {
        $output        = $completedStepRun->output ?? [];
        $branch        = $output['branch'] ?? 'false';
        $branches      = $stepDef['branches'] ?? [];
        $chosenStepId  = $branches[$branch] ?? null;
        $rejectedStepId = $branches[$branch === 'true' ? 'false' : 'true'] ?? null;

        // Mark rejected sebagai skipped
        if ($rejectedStepId) {
            $affected = StepRun::where('workflow_run_id', $workflowRun->id)
                ->where('step_id', $rejectedStepId)
                ->whereIn('status', ['pending', 'queued']) // ← guard queued juga
                ->update([
                    'status'      => 'skipped',
                    'finished_at' => now(),
                ]);

            if ($affected > 0) {
                $rejectedRun = StepRun::where('workflow_run_id', $workflowRun->id)
                    ->where('step_id', $rejectedStepId)
                    ->first();

                event(new \App\Events\StepSkipped($rejectedRun));
            }
        }

        // Dispatch chosen — atomic
        if ($chosenStepId) {
            $affected = StepRun::where('workflow_run_id', $workflowRun->id)
                ->where('step_id', $chosenStepId)
                ->where('status', 'pending')
                ->update(['status' => 'queued']);

            if ($affected > 0) {
                $chosenRun = StepRun::where('workflow_run_id', $workflowRun->id)
                    ->where('step_id', $chosenStepId)
                    ->first();

                RunStepJob::dispatch($chosenRun->id);
            }
        }
    }

    /**
     * Dispatch steps yang depends_on-nya sudah semua selesai (success/skipped).
     */
    private function dispatchByDependencies(WorkflowRun $workflowRun, array $definition): void
    {
        $satisfied = StepRun::where('workflow_run_id', $workflowRun->id)
            ->whereIn('status', ['success', 'skipped'])
            ->pluck('step_id')
            ->map(fn($v) => (string) $v)
            ->toArray();

        $parser = new \App\Services\Workflow\DagParser();
        $next   = $parser->getNextExecutableSteps($definition, $satisfied);

        foreach ($next as $stepId) {
            $affected = StepRun::where('workflow_run_id', $workflowRun->id)
                ->where('step_id', $stepId)
                ->where('status', 'pending')
                ->update(['status' => 'queued']);

            if ($affected > 0) {
                // Hanya dispatch kalau kita yang berhasil update
                $stepRun = StepRun::where('workflow_run_id', $workflowRun->id)
                    ->where('step_id', $stepId)
                    ->first();

                RunStepJob::dispatch($stepRun->id);
            }
        }
    }

    /**
     * Cek apakah semua step sudah selesai, lalu complete workflow.
     */
    private function checkAndCompleteWorkflow(WorkflowRun $workflowRun): void
    {
        $unfinished = StepRun::where('workflow_run_id', $workflowRun->id)
            ->whereIn('status', ['pending', 'queued', 'running']) // ← tambah queued
            ->exists();

        if (! $unfinished) {
            $workflowRun->refresh();

            if ($workflowRun->status !== 'completed') {
                $workflowRun->update(['status' => 'completed']);
                event(new \App\Events\WorkflowCompleted($workflowRun));
            }
        }
    }
}
