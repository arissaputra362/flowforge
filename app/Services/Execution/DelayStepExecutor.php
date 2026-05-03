<?php

namespace App\Services\Execution;

class DelayStepExecutor
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        // $seconds = (int) ($stepDefinition['seconds'] ?? 1);

        // sleep($seconds);

        // return ['slept' => $seconds];

        $config = $stepDefinition['config'] ?? [];

        $ms = (int) ($config['duration'] ?? $config['seconds'] ?? 1000);

        usleep($ms * 1000); // ms → microseconds

        return [
            'slept_ms' => $ms,
        ];
    }
}
