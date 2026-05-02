<?php

namespace App\Services\Execution;

class ThrowingStepExecutor
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        throw new \Exception($stepDefinition['message'] ?? 'step failed');
    }
}
