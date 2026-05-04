<?php

namespace Tests\Unit;

use App\Services\Workflow\DagParser;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DagParserTest extends TestCase
{
    public function test_valid_dag_passes_and_returns_execution_order(): void
    {
        $parser = new DagParser();

        $parsed = $parser->parse([
            'steps' => [
                [
                    'id' => 'A',
                    'type' => 'http',
                    'depends_on' => [],
                ],
                [
                    'id' => 'B',
                    'type' => 'task',
                    'depends_on' => ['A'],
                ],
                [
                    'id' => 'C',
                    'type' => 'delay',
                    'depends_on' => ['A'],
                ],
            ],
        ]);

        $this->assertSame(['A', 'B', 'C'], $parsed['execution_order']);
        $this->assertSame(['A'], $parsed['root_steps']);
        $this->assertSame('A', $parsed['definition']['steps'][0]['id']);
    }

    public function test_missing_dependency_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new DagParser();

        $parser->parse([
            'steps' => [
                [
                    'id' => 'A',
                    'type' => 'task',
                    'depends_on' => ['UNKNOWN'],
                ],
            ],
        ]);
    }

    public function test_self_dependency_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new DagParser();

        $parser->parse([
            'steps' => [
                [
                    'id' => 'A',
                    'type' => 'task',
                    'depends_on' => ['A'],
                ],
            ],
        ]);
    }

    public function test_circular_dependency_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new DagParser();

        $parser->parse([
            'steps' => [
                [
                    'id' => 'A',
                    'type' => 'task',
                    'depends_on' => ['C'],
                ],
                [
                    'id' => 'B',
                    'type' => 'task',
                    'depends_on' => ['A'],
                ],
                [
                    'id' => 'C',
                    'type' => 'task',
                    'depends_on' => ['B'],
                ],
            ],
        ]);
    }

    public function test_multiple_root_steps_are_supported(): void
    {
        $parser = new DagParser();

        $roots = $parser->getRootSteps([
            'steps' => [
                [
                    'id' => 'A',
                    'type' => 'http',
                    'depends_on' => [],
                ],
                [
                    'id' => 'B',
                    'type' => 'delay',
                    'depends_on' => [],
                ],
                [
                    'id' => 'C',
                    'type' => 'task',
                    'depends_on' => ['A', 'B'],
                ],
            ],
        ]);

        $this->assertSame(['A', 'B'], $roots);
    }

    public function test_next_executable_steps_respects_completed_steps(): void
    {
        $parser = new DagParser();

        $nextSteps = $parser->getNextExecutableSteps([
            'steps' => [
                [
                    'id' => 'A',
                    'type' => 'http',
                    'depends_on' => [],
                ],
                [
                    'id' => 'B',
                    'type' => 'delay',
                    'depends_on' => ['A'],
                ],
                [
                    'id' => 'C',
                    'type' => 'task',
                    'depends_on' => ['A'],
                ],
            ],
        ], ['A']);

        $this->assertSame(['B', 'C'], $nextSteps);
    }

    public function test_root_steps_exclude_condition_branch_targets(): void
    {
        $parser = new DagParser();

        $roots = $parser->getRootSteps([
            'steps' => [
                [
                    'id' => 'check',
                    'type' => 'condition',
                    'depends_on' => [],
                    'branches' => [
                        'true' => 'approve',
                        'false' => 'reject',
                    ],
                ],
                [
                    'id' => 'approve',
                    'type' => 'script',
                    'depends_on' => [],
                ],
                [
                    'id' => 'reject',
                    'type' => 'script',
                    'depends_on' => [],
                ],
            ],
        ]);

        $this->assertSame(['check'], $roots);
    }

    public function test_next_executable_steps_skip_branch_targets(): void
    {
        $parser = new DagParser();

        $nextSteps = $parser->getNextExecutableSteps([
            'steps' => [
                [
                    'id' => 'check',
                    'type' => 'condition',
                    'depends_on' => [],
                    'branches' => [
                        'true' => 'approve',
                        'false' => 'reject',
                    ],
                ],
                [
                    'id' => 'approve',
                    'type' => 'script',
                    'depends_on' => [],
                ],
                [
                    'id' => 'reject',
                    'type' => 'script',
                    'depends_on' => [],
                ],
            ],
        ], []);

        $this->assertSame(['check'], $nextSteps);
    }
}
