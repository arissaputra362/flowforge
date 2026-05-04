<?php

namespace Tests\Unit;

use App\Services\Execution\ConditionStepExecutor;
use PHPUnit\Framework\TestCase;

class ConditionStepExecutorTest extends TestCase
{
    public function test_condition_expression_evaluates_true_with_normalized_step_id(): void
    {
        $result = ConditionStepExecutor::execute([
            'type' => 'condition',
            'config' => [
                'expression' => 'step.step_1.output.count >= 5',
            ],
        ], [
            'steps' => [
                'step_1' => [
                    'output' => [
                        'count' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['result']);
        $this->assertSame('true', $result['branch']);
    }

    public function test_condition_expression_evaluates_false_on_mismatch(): void
    {
        $result = ConditionStepExecutor::execute([
            'type' => 'condition',
            'config' => [
                'expression' => 'step.step_1.output.status == "ok"',
            ],
        ], [
            'steps' => [
                'step_1' => [
                    'output' => [
                        'status' => 'fail',
                    ],
                ],
            ],
        ]);

        $this->assertFalse($result['result']);
        $this->assertSame('false', $result['branch']);
    }

    public function test_missing_expression_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConditionStepExecutor::execute([
            'type' => 'condition',
            'config' => [],
        ], []);
    }
}
