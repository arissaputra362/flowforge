<?php

namespace App\Services\Execution;

class ScriptStepExecutor
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        $config = $stepDefinition['config'] ?? [];

        $code = $config['code'] ?? null;

        if (!$code) {
            throw new \InvalidArgumentException('Script step missing code');
        }

        return [
            'output' => "executed script",
            'code'   => $code
        ];
    }
}
