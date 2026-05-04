<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Trigger;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_token_starts_workflow_run_with_payload(): void
    {
        Queue::fake();

        $workflow = $this->createWebhookWorkflow('valid-token');

        $response = $this->postJson('/api/webhooks/valid-token', [
            'event' => 'user.created',
            'id' => 123,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Workflow triggered')
            ->assertJsonPath('workflow_id', $workflow->id);

        $this->assertDatabaseHas('workflow_runs', [
            'workflow_id' => $workflow->id,
            'tenant_id' => $workflow->tenant_id,
            'status' => 'running',
        ]);

        $run = $workflow->runs()->latest()->first();

        $this->assertSame('user.created', data_get($run->input, 'payload.event'));
        $this->assertSame(123, data_get($run->input, 'payload.id'));
        $this->assertNotEmpty(data_get($run->input, 'received_at'));
    }

    public function test_invalid_webhook_token_returns_not_found(): void
    {
        $this->postJson('/api/webhooks/missing-token', ['event' => 'x'])
            ->assertNotFound();
    }

    public function test_disabled_webhook_trigger_cannot_start_workflow(): void
    {
        $workflow = $this->createWebhookWorkflow('disabled-token', false);

        $this->postJson('/api/webhooks/disabled-token', ['event' => 'x'])
            ->assertNotFound();

        $this->assertDatabaseMissing('workflow_runs', [
            'workflow_id' => $workflow->id,
        ]);
    }

    private function createWebhookWorkflow(string $token, bool $enabled = true): Workflow
    {
        $tenant = Tenant::create([
            'name' => 'Webhook Tenant',
            'metadata' => [],
        ]);

        $workflow = Workflow::create([
            'tenant_id' => $tenant->id,
            'name' => 'Webhook Workflow',
            'description' => null,
        ]);

        $workflow->versions()->create([
            'version' => '1',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'delay-1',
                        'type' => 'delay',
                        'depends_on' => [],
                        'config' => ['duration' => 1],
                    ],
                ],
            ],
        ]);

        Trigger::create([
            'workflow_id' => $workflow->id,
            'type' => 'webhook',
            'enabled' => $enabled,
            'config' => [
                'webhook_token' => $token,
            ],
        ]);

        return $workflow;
    }
}
