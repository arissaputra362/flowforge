<?php

namespace Tests\Unit;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Models\StepRun;
use App\Services\ExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_step_records_error_and_marks_failed_after_max_attempts()
    {
        $tenant = \App\Models\Tenant::create(['name' => 't']);
        $workflow = Workflow::create(['name' => 'w', 'tenant_id' => $tenant->id]);

        $definition = [
            'steps' => [
                ['id' => 'X', 'type' => 'throw', 'depends_on' => [], 'message' => 'boom'],
            ],
            'root_steps' => ['X'],
        ];

        $version = WorkflowVersion::create(['workflow_id' => $workflow->id, 'version' => '1', 'definition' => $definition]);

        $run = WorkflowRun::create(['workflow_id' => $workflow->id, 'workflow_version_id' => $version->id, 'tenant_id' => $tenant->id, 'status' => 'running', 'input' => []]);

        $stepRun = StepRun::create(['workflow_run_id' => $run->id, 'step_id' => 'X', 'status' => 'pending', 'attempt' => 0]);

        $service = new ExecutionService();

        // first attempt -> throws and increments attempt to 1
        try {
            $service->executeStepAttempt($stepRun->id, 2);
            $this->fail('Expected exception on first attempt');
        } catch (\Throwable $e) {
            $stepRun->refresh();
            $this->assertEquals(1, $stepRun->attempt);
            $this->assertStringContainsString('boom', $stepRun->last_error);
            $this->assertNotEquals('failed', $stepRun->status);
        }

        // second attempt -> should mark failed (maxAttempts=2)
        $service->executeStepAttempt($stepRun->id, 2);

        $stepRun->refresh();

        $this->assertEquals(2, $stepRun->attempt);
        $this->assertEquals('failed', $stepRun->status);
        $this->assertStringContainsString('boom', $stepRun->last_error);
    }
}
