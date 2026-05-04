<?php

namespace Tests\Unit;

use App\Services\Execution\ScriptStepExecutor;
use PHPUnit\Framework\TestCase;

class ScriptStepExecutorTest extends TestCase
{
    public function test_script_executes_and_returns_code(): void
    {
        $result = ScriptStepExecutor::execute([
            'type' => 'script',
            'config' => [
                'code' => 'return 1 + 1;',
            ],
        ]);

        $this->assertSame('executed script', $result['output']);
        $this->assertSame('return 1 + 1;', $result['code']);
    }

    public function test_missing_code_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScriptStepExecutor::execute([
            'type' => 'script',
            'config' => [],
        ]);
    }
}
