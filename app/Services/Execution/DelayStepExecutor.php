<?php

namespace App\Services\Execution;

class DelayStepExecutor
{
    /**
     * Execute a delay step. Duration is configured in config.duration (ms) or config.seconds.
     *
     * Note: Individual step timeout (if configured) is mainly for HTTP steps.
     * For delay steps, the RunStepJob timeout (300s) is the hard limit.
     */
    public static function execute(array $stepDefinition, array $input = []): array
    {
        // $seconds = (int) ($stepDefinition['seconds'] ?? 1);

        // sleep($seconds);

        // return ['slept' => $seconds];

        $config = $stepDefinition['config'] ?? [];

        $ms = isset($config['duration'])
            ? (int) $config['duration']
            : (int) (($config['seconds'] ?? 1) * 1000);

        usleep($ms * 1000); // ms → microseconds

        return [
            'slept_ms' => $ms,
        ];
    }
}
