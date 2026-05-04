<?php

namespace Tests\Unit;

use App\Models\StepRun;
use App\Models\Tenant;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\ExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_workflow_run_is_marked_failed_with_pending_steps_failed(): void
    {
        $tenant = Tenant::create([
            'name' => 'Timeout Tenant',
            'metadata' => [],
        ]);

        $workflow = Workflow::create([
            'tenant_id' => $tenant->id,
            'name' => 'Timeout Workflow',
            'description' => null,
        ]);

        $version = $workflow->versions()->create([
            'version' => '1',
            'definition' => [
                'workflow_timeout_seconds' => 1,
                'steps' => [
                    [
                        'id' => 'delay-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                ],
            ],
        ]);

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $tenant->id,
            'status' => 'running',
            'input' => [],
        ]);

        $run->forceFill([
            'created_at' => now()->subSeconds(5),
            'updated_at' => now()->subSeconds(5),
        ])->save();

        $stepRun = StepRun::create([
            'workflow_run_id' => $run->id,
            'step_id' => 'delay-1',
            'attempt' => 0,
            'status' => 'queued',
            'input' => [],
        ]);

        app(ExecutionService::class)->executeStepAttempt($stepRun->id, 3);

        $run->refresh();
        $stepRun->refresh();

        $this->assertSame('failed', $run->status);
        $this->assertSame('failed', $stepRun->status);
        $this->assertSame('Workflow timed out', $stepRun->last_error);
    }
}
