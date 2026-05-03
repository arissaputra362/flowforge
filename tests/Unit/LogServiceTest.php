<?php

namespace Tests\Unit;

use App\Models\ExecutionLog;
use App\Models\StepRun;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Services\LogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogServiceTest extends TestCase
{
    use RefreshDatabase;

    private LogService $logService;
    private StepRun $stepRun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logService = new LogService();

        $tenant = \App\Models\Tenant::create([
            'name' => 'Test Tenant',
        ]);
        $tenantId = $tenant->id;

        // Create dummy data
        $workflow = Workflow::create([
            'tenant_id' => $tenantId,
            'name' => 'Test Workflow',
        ]);

        $version = WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => '1',
            'definition' => [],
        ]);

        $workflowRun = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $tenantId,
            'status' => 'running',
            'input' => [],
        ]);

        $this->stepRun = StepRun::create([
            'workflow_run_id' => $workflowRun->id,
            'step_id' => 'step-1',
            'status' => 'pending',
            'attempt' => 1,
            'input' => [],
        ]);
    }

    public function test_info_log_creates_record_with_correct_level_and_context()
    {
        $this->logService->info($this->stepRun, 'Step started', ['foo' => 'bar']);

        $this->assertDatabaseHas('execution_logs', [
            'step_run_id' => $this->stepRun->id,
            'workflow_run_id' => $this->stepRun->workflow_run_id,
            'level' => 'info',
            'message' => 'Step started',
        ]);

        $repo = new \App\Repositories\ExecutionLogRepository();
        $log = $repo->first();
        
        $this->assertEquals('bar', $log->context['foo']);
        $this->assertEquals($this->stepRun->workflow_run_id, $log->context['correlation_id']);
        $this->assertEquals('step-1', $log->context['step_id']);
        $this->assertEquals(1, $log->context['attempt']);
    }

    public function test_error_log_creates_record_with_error_level()
    {
        $this->logService->error($this->stepRun, 'Step failed', ['error' => 'Timeout']);

        $this->assertDatabaseHas('execution_logs', [
            'step_run_id' => $this->stepRun->id,
            'level' => 'error',
            'message' => 'Step failed',
        ]);

        $repo = new \App\Repositories\ExecutionLogRepository();
        $log = $repo->firstByLevel('error');
        
        $this->assertEquals('Timeout', $log->context['error']);
    }

    public function test_custom_correlation_id_can_be_provided()
    {
        $this->logService->info($this->stepRun, 'Test', ['correlation_id' => 'custom-123']);

        $repo = new \App\Repositories\ExecutionLogRepository();
        $log = $repo->first();
        
        $this->assertEquals('custom-123', $log->context['correlation_id']);
    }
}
