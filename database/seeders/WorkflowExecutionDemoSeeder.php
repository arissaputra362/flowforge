<?php

namespace Database\Seeders;

use App\Models\StepRun;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowExecutionDemoSeeder extends Seeder
{
    /**
     * Seed data needed for realtime and execution-event testing.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::query()->first();

            $tenant = Tenant::query()->firstOrCreate(
                ['name' => 'Tenant Realtime Demo'],
                [
                    'metadata' => ['tier' => 'demo'],
                    'created_by' => $user?->id,
                    'updated_by' => $user?->id,
                ]
            );

            $successDefinition = [
                'steps' => [
                    ['id' => 'A', 'type' => 'delay', 'seconds' => 0, 'depends_on' => []],
                    ['id' => 'B', 'type' => 'delay', 'seconds' => 0, 'depends_on' => ['A']],
                ],
            ];

            $failureDefinition = [
                'steps' => [
                    ['id' => 'X', 'type' => 'throw', 'message' => 'seeded failure', 'depends_on' => []],
                ],
            ];

            $this->seedWorkflowRunTemplate(
                tenant: $tenant,
                userId: $user?->id,
                workflowName: 'Realtime Demo Workflow Success',
                definition: $successDefinition
            );

            $this->seedWorkflowRunTemplate(
                tenant: $tenant,
                userId: $user?->id,
                workflowName: 'Realtime Demo Workflow Failure',
                definition: $failureDefinition
            );
        });
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function seedWorkflowRunTemplate(Tenant $tenant, ?string $userId, string $workflowName, array $definition): void
    {
        $workflow = Workflow::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => $workflowName,
            ],
            [
                'description' => 'Seeded workflow for realtime demo and event tests.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        $version = WorkflowVersion::query()->firstOrCreate(
            [
                'workflow_id' => $workflow->id,
                'version' => '1',
            ],
            [
                'definition' => $definition,
                'notes' => 'Seeded version for execution tests',
            ]
        );

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $tenant->id,
            'created_by' => $userId,
            'updated_by' => $userId,
            'started_by' => $userId,
            'status' => 'running',
            'input' => ['source' => 'seeder'],
        ]);

        foreach (($definition['steps'] ?? []) as $step) {
            StepRun::query()->create([
                'workflow_run_id' => $run->id,
                'created_by' => $userId,
                'updated_by' => $userId,
                'step_id' => (string) ($step['id'] ?? 'unknown'),
                'attempt' => 0,
                'status' => 'pending',
                'input' => ['source' => 'seeder'],
            ]);
        }

        if ($this->command) {
            $this->command->line('Seeded workflow run: ' . $run->id . ' (' . $workflowName . ')');
            $this->command->line('Realtime demo URL: /realtime-demo?run_id=' . $run->id);
        }
    }
}
