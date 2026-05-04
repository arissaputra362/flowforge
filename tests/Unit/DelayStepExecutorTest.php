<?php

namespace Tests\Unit;

use App\Services\Execution\DelayStepExecutor;
use PHPUnit\Framework\TestCase;

class DelayStepExecutorTest extends TestCase
{
    public function test_seconds_config_is_converted_to_milliseconds(): void
    {
        $startedAt = microtime(true);

        $result = DelayStepExecutor::execute([
            'type' => 'delay',
            'config' => [
                'seconds' => 0.001,
            ],
        ]);

        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        $this->assertSame(1, $result['slept_ms']);
        $this->assertGreaterThanOrEqual(0.8, $elapsedMs);
    }

    public function test_duration_config_stays_in_milliseconds(): void
    {
        $result = DelayStepExecutor::execute([
            'type' => 'delay',
            'config' => [
                'duration' => 7,
                'seconds' => 10,
            ],
        ]);

        $this->assertSame(7, $result['slept_ms']);
    }
}
