<?php

namespace App\Services;

use App\Models\WorkflowRun;
use App\Repositories\TenantRepository;
use App\Repositories\WorkflowRepository;

class DemoService
{
    private WorkflowRepository $workflowRepository;
    private TenantRepository $tenantRepository;
    private ExecutionService $executionService;

    public function __construct(
        WorkflowRepository $workflowRepository,
        TenantRepository $tenantRepository,
        ExecutionService $executionService
    ) {
        $this->workflowRepository = $workflowRepository;
        $this->tenantRepository = $tenantRepository;
        $this->executionService = $executionService;
    }

    public function triggerRealtimeDemo(): WorkflowRun
    {
        $tenant = $this->tenantRepository->firstOrCreate([
            'name' => 'Demo Tenant'
        ]);
        
        $workflow = $this->workflowRepository->create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Workflow',
            'description' => 'For testing realtime events',
        ], [
            'steps' => [
                ['id' => 'step-1', 'type' => 'delay', 'seconds' => 1, 'depends_on' => []],
                ['id' => 'step-2', 'type' => 'throw', 'message' => 'Connection refused to external API', 'depends_on' => ['step-1']],
                ['id' => 'step-3', 'type' => 'delay', 'seconds' => 1, 'depends_on' => ['step-2']],
            ],
            'root_steps' => ['step-1']
        ]);

        $run = $this->executionService->startWorkflow($workflow->id);
        
        // Fire the workflow started event (normally done by RunWorkflowJob)
        event(new \App\Events\WorkflowStarted($run));

        return $run;
    }
}
