<?php

namespace Tests\Unit;

use App\Models\ExecutionLog;
use App\Models\StepRun;
use App\Models\WorkflowRun;
use App\Repositories\RunMonitorRepository;
use App\Services\RunMonitorService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RunMonitorServiceTest extends TestCase
{
    public function test_poll_returns_steps_logs_and_last_seq(): void
    {
        $run = new WorkflowRun(['id' => 'run-1', 'status' => 'running']);
        $run->id = 'run-1';

        $step = new StepRun([
            'step_id' => 'step-1',
            'status' => 'success',
            'attempt' => 1,
            'output' => [
                'body' => str_repeat('a', 600),
            ],
        ]);

        $log1 = new ExecutionLog(['seq' => 10, 'level' => 'info', 'message' => 'start']);
        $log2 = new ExecutionLog(['seq' => 11, 'level' => 'info', 'message' => 'done']);

        $repo = $this->createMock(RunMonitorRepository::class);
        $repo->method('findRunForTenant')->willReturn($run);
        $repo->method('listStepStates')->willReturn(new Collection([$step]));
        $repo->method('listLogsSince')->willReturn(new Collection([$log1, $log2]));

        $service = new RunMonitorService($repo);

        $payload = $service->poll('run-1', null, 0);

        $this->assertSame('run-1', $payload['run']['id']);
        $this->assertSame('running', $payload['run']['status']);
        $this->assertSame(11, $payload['last_seq']);
        $this->assertCount(1, $payload['steps']);
        $this->assertCount(2, $payload['logs']);

        $body = $payload['steps'][0]['output']['body'] ?? '';
        $this->assertTrue(str_ends_with($body, '...[truncated]'));
    }
}
