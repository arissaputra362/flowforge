<?php

namespace Tests\Unit;

use App\Jobs\RunStepJob;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Models\StepRun;
use App\Services\ExecutionService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExecutionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_initialize_run_creates_step_runs_and_dispatches_root_steps()
    {
        Bus::fake();
        Http::fake();

        $tenant = \App\Models\Tenant::create(['name' => 'test-tenant']);
        $workflow = Workflow::create(['name' => 'test-workflow', 'tenant_id' => $tenant->id]);

        $definition = [
            'steps' => [
                ['id' => 'A', 'type' => 'delay', 'depends_on' => []],
                ['id' => 'B', 'type' => 'delay', 'depends_on' => ['A']],
                ['id' => 'C', 'type' => 'delay', 'depends_on' => ['B']],
            ],
            'root_steps' => ['A'],
        ];

        $version = WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => '1',
            'definition' => $definition,
        ]);

        $workflowRun = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $workflow->tenant_id,
            'status' => 'running',
            'input' => [],
        ]);

        $service = new ExecutionService();

        $service->initializeRun($workflowRun, $version, $definition, []);

        $this->assertDatabaseCount('step_runs', 3);

        Bus::assertDispatched(RunStepJob::class, function ($job) use ($workflowRun) {
            $stepRun = StepRun::find($job->stepRunId);

            return $stepRun && $stepRun->step_id === 'A';
        });
    }

    public function test_dispatch_next_steps_after_success()
    {
        Bus::fake();

        $tenant = \App\Models\Tenant::create(['name' => 'test-tenant']);
        $workflow = Workflow::create(['name' => 'test-workflow', 'tenant_id' => $tenant->id]);

        $definition = [
            'steps' => [
                ['id' => 'A', 'type' => 'delay', 'depends_on' => []],
                ['id' => 'B', 'type' => 'delay', 'depends_on' => ['A']],
            ],
            'root_steps' => ['A'],
        ];

        $version = WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => '1',
            'definition' => $definition,
        ]);

        $workflowRun = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $workflow->tenant_id,
            'status' => 'running',
            'input' => [],
        ]);

        // create step runs
        StepRun::create(['workflow_run_id' => $workflowRun->id, 'step_id' => 'A', 'status' => 'success', 'attempt' => 1]);
        StepRun::create(['workflow_run_id' => $workflowRun->id, 'step_id' => 'B', 'status' => 'pending', 'attempt' => 0]);

        $service = new ExecutionService();

        $service->dispatchNextSteps($workflowRun, StepRun::where('step_id', 'A')->first());

        Bus::assertDispatched(RunStepJob::class, function ($job) use ($workflowRun) {
            $stepRun = StepRun::find($job->stepRunId);

            return $stepRun && $stepRun->step_id === 'B';
        });
    }
}
