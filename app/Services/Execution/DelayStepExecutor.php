<?php

namespace App\Services\Execution;

class DelayStepExecutor
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        $seconds = (int) ($stepDefinition['seconds'] ?? 1);

        sleep($seconds);

        return ['slept' => $seconds];
    }
}
