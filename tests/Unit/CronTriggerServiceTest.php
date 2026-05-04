<?php

namespace Tests\Unit;

use App\Models\Trigger;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Repositories\CronTriggerRepository;
use App\Services\CronTriggerService;
use App\Services\ExecutionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CronTriggerServiceTest extends TestCase
{
    public function test_due_cron_trigger_executes_workflow(): void
    {
        $now = Carbon::parse('2026-05-04 10:00:00', 'UTC');
        $windowStart = $now->copy()->startOfMinute();

        $workflow = new Workflow();
        $workflow->id = 'workflow-1';
        $workflow->setRelation('latestVersion', new WorkflowVersion());

        $trigger = new Trigger([
            'id' => 'trigger-1',
            'config' => [
                'cron_expression' => '* * * * *',
                'cron_timezone' => 'UTC',
            ],
        ]);
        $trigger->setRelation('workflow', $workflow);

        $repo = $this->createMock(CronTriggerRepository::class);
        $repo->method('listEnabledCronTriggers')->willReturn(new Collection([$trigger]));
        $repo->expects($this->once())
            ->method('markTriggered')
            ->with(
                'trigger-1',
                $this->callback(fn($value) => $value->equalTo($windowStart)),
                $this->callback(fn($value) => $value->equalTo($now))
            )
            ->willReturn(true);

        $executionService = $this->createMock(ExecutionService::class);
        $executionService->expects($this->once())
            ->method('startWorkflow')
            ->with('workflow-1', []);

        $service = new CronTriggerService($repo, $executionService);

        $summary = $service->runDueTriggers($now);

        $this->assertSame(['checked' => 1, 'triggered' => 1, 'skipped' => 0], $summary);
    }

    public function test_invalid_cron_expression_is_skipped(): void
    {
        $now = Carbon::parse('2026-05-04 10:00:00', 'UTC');

        $workflow = new Workflow();
        $workflow->id = 'workflow-2';
        $workflow->setRelation('latestVersion', new WorkflowVersion());

        $trigger = new Trigger([
            'id' => 'trigger-2',
            'config' => [
                'cron_expression' => 'invalid',
            ],
        ]);
        $trigger->setRelation('workflow', $workflow);

        $repo = $this->createMock(CronTriggerRepository::class);
        $repo->method('listEnabledCronTriggers')->willReturn(new Collection([$trigger]));
        $repo->expects($this->never())->method('markTriggered');

        $executionService = $this->createMock(ExecutionService::class);
        $executionService->expects($this->never())->method('startWorkflow');

        $service = new CronTriggerService($repo, $executionService);

        $summary = $service->runDueTriggers($now);

        $this->assertSame(['checked' => 1, 'triggered' => 0, 'skipped' => 1], $summary);
    }
}
