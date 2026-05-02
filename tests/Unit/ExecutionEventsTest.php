<?php

namespace Tests\Unit;

use App\Events\StepFailed;
use App\Events\StepStarted;
use App\Events\StepSucceeded;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowStarted;
use App\Jobs\RunWorkflowJob;
use App\Models\StepRun;
use App\Models\Tenant;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Services\ExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExecutionEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_step_and_workflow_events_for_successful_execution(): void
    {
        Event::fake([StepStarted::class, StepSucceeded::class, WorkflowCompleted::class]);

        $tenant = Tenant::create(['name' => 'Realtime Tenant']);
        $workflow = Workflow::create(['tenant_id' => $tenant->id, 'name' => 'Realtime Workflow']);

        $definition = [
            'steps' => [
                ['id' => 'A', 'type' => 'delay', 'seconds' => 0, 'depends_on' => []],
            ],
        ];

        $version = WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => '1',
            'definition' => $definition,
        ]);

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $tenant->id,
            'status' => 'running',
            'input' => [],
        ]);

        $stepRun = StepRun::create([
            'workflow_run_id' => $run->id,
            'step_id' => 'A',
            'attempt' => 0,
            'status' => 'pending',
            'input' => [],
        ]);

        (new ExecutionService())->executeStepAttempt((string) $stepRun->id, 3);

        Event::assertDispatched(StepStarted::class);
        Event::assertDispatched(StepSucceeded::class);
        Event::assertDispatched(WorkflowCompleted::class);

        $run->refresh();
        $this->assertSame('completed', $run->status);
    }

    public function test_it_dispatches_step_failed_when_retry_limit_reached(): void
    {
        Event::fake([StepFailed::class]);

        $tenant = Tenant::create(['name' => 'Retry Tenant']);
        $workflow = Workflow::create(['tenant_id' => $tenant->id, 'name' => 'Retry Workflow']);

        $definition = [
            'steps' => [
                ['id' => 'X', 'type' => 'throw', 'message' => 'boom', 'depends_on' => []],
            ],
        ];

        $version = WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => '1',
            'definition' => $definition,
        ]);

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $tenant->id,
            'status' => 'running',
            'input' => [],
        ]);

        $stepRun = StepRun::create([
            'workflow_run_id' => $run->id,
            'step_id' => 'X',
            'attempt' => 0,
            'status' => 'pending',
            'input' => [],
        ]);

        // maxAttempts=1 means immediate terminal failure on first thrown error.
        (new ExecutionService())->executeStepAttempt((string) $stepRun->id, 1);

        Event::assertDispatched(StepFailed::class);

        $stepRun->refresh();
        $this->assertSame('failed', $stepRun->status);
        $this->assertSame(1, $stepRun->attempt);
    }

    public function test_run_workflow_job_dispatches_workflow_started_event(): void
    {
        Event::fake([WorkflowStarted::class]);

        $tenant = Tenant::create(['name' => 'Start Tenant']);
        $workflow = Workflow::create(['tenant_id' => $tenant->id, 'name' => 'Start Workflow']);

        WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => '1',
            'definition' => [
                'steps' => [
                    ['id' => 'A', 'type' => 'delay', 'seconds' => 0, 'depends_on' => []],
                ],
            ],
        ]);

        (new RunWorkflowJob((string) $workflow->id, []))->handle(new ExecutionService());

        Event::assertDispatched(WorkflowStarted::class);
    }
}
