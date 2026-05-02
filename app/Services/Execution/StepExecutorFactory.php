<?php

namespace App\Services\Execution;

use Illuminate\Support\Facades\Http;
use App\Services\Execution\HttpStepExecutor;
use App\Services\Execution\DelayStepExecutor;
use App\Services\Execution\ThrowingStepExecutor;

class StepExecutorFactory
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        $type = $stepDefinition['type'] ?? 'unknown';

        return match ($type) {
            'http' => HttpStepExecutor::execute($stepDefinition, $input),
            'delay' => DelayStepExecutor::execute($stepDefinition, $input),
            'throw' => ThrowingStepExecutor::execute($stepDefinition, $input),
            default => throw new \RuntimeException("Unsupported step type: {$type}"),
        };
    }
}
