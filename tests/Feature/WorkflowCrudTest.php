<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowCrudTest extends TestCase
{
   use RefreshDatabase;

    public function test_it_creates_a_workflow_with_initial_version(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'metadata' => ['tier' => 'pro'],
        ]);
        $this->actingAsTenantUser($tenant);

        $response = $this->postJson('/api/workflows', [
            'name' => 'Test Workflow',
            'description' => 'Workflow for testing',
            'trigger_type' => 'manual',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('name', 'Test Workflow');
        $response->assertJsonPath('latestVersion.version', '1');
        $response->assertJsonPath('latestVersion.definition.steps.0.id', 'step-1');

        $this->assertDatabaseHas('workflows', [
            'tenant_id' => $tenant->id,
            'name' => 'Test Workflow',
        ]);

        $this->assertDatabaseHas('workflow_versions', [
            'version' => '1',
        ]);
    }

    public function test_it_updates_a_workflow_by_creating_a_new_version(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant B',
            'metadata' => ['tier' => 'standard'],
        ]);
        $this->actingAsTenantUser($tenant);

        $workflow = Workflow::create([
            'tenant_id' => $tenant->id,
            'name' => 'Original Workflow',
            'description' => 'Original description',
        ]);

        $workflow->versions()->create([
            'version' => '1',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                ],
            ],
        ]);

        $response = $this->putJson('/api/workflows/'.$workflow->id, [
            'name' => 'Updated Workflow',
            'description' => 'Updated description',
            'trigger_type' => 'manual',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                    [
                        'id' => 'step-2',
                        'type' => 'task',
                        'depends_on' => ['step-1'],
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'Updated Workflow');
        $response->assertJsonPath('latestVersion.version', '2');
        $response->assertJsonPath('latestVersion.definition.steps.1.id', 'step-2');

        $this->assertDatabaseHas('workflow_versions', [
            'workflow_id' => $workflow->id,
            'version' => '1',
        ]);

        $this->assertDatabaseHas('workflow_versions', [
            'workflow_id' => $workflow->id,
            'version' => '2',
        ]);
    }

    public function test_it_returns_the_latest_version_when_showing_a_workflow(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant C',
            'metadata' => [],
        ]);
        $this->actingAsTenantUser($tenant);

        $workflow = Workflow::create([
            'tenant_id' => $tenant->id,
            'name' => 'Show Workflow',
            'description' => null,
        ]);

        $workflow->versions()->create([
            'version' => '1',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                ],
            ],
        ]);

        $workflow->versions()->create([
            'version' => '2',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                    [
                        'id' => 'step-2',
                        'type' => 'task',
                        'depends_on' => ['step-1'],
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/api/workflows/'.$workflow->id.'?tenant_id='.$tenant->id);

        $response->assertOk();
        $response->assertJsonPath('latestVersion.version', '2');
        $response->assertJsonPath('latestVersion.definition.steps.1.id', 'step-2');
    }

    public function test_it_lists_workflows_filtered_by_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'metadata' => [],
        ]);
        $this->actingAsTenantUser($tenantA);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'metadata' => [],
        ]);

        $workflowA = Workflow::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Workflow A',
            'description' => null,
        ]);

        $workflowA->versions()->create([
            'version' => '1',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                ],
            ],
        ]);

        $workflowB = Workflow::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Workflow B',
            'description' => null,
        ]);

        $workflowB->versions()->create([
            'version' => '1',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'type' => 'delay',
                        'depends_on' => [],
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/api/workflows?tenant_id='.$tenantA->id.'&per_page=15');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Workflow A');
        $response->assertJsonMissing(['name' => 'Workflow B']);
    }

    private function actingAsTenantUser(Tenant $tenant): User
    {
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User',
            'email' => 'tenant-'.str()->uuid().'@example.test',
            'password' => 'password',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }
}
