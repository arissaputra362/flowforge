<?php

namespace App\Services\Execution;

class ConditionStepExecutor
{
    public static function execute(array $stepDefinition, array $context = []): array
    {
        $config     = $stepDefinition['config'] ?? [];
        $expression = $config['expression'] ?? null;

        if (! $expression) {
            throw new \InvalidArgumentException('Condition missing expression');
        }

        $result = self::evaluate($expression, $context);

        return [
            'result' => $result,
            'branch' => $result ? 'true' : 'false',
        ];
    }

    private static function evaluate(string $expression, array $context): bool
    {
        // Build normalized lookup: 'step_1' → 'step1', 'my_step' → 'mystep'
        $stepsOutput = $context['steps'] ?? [];
        $normalizedSteps = [];

        foreach ($stepsOutput as $stepId => $data) {
            $normalizedKey = str_replace('_', '', $stepId); // 'step_1' → 'step1'
            $normalizedSteps[$normalizedKey] = $data;
        }

        // Pattern: step.<step_id>.output.<field> <op> <value>
        if (preg_match(
            '/^step\.(\w+)\.output\.(\w+)\s*(>=|<=|==|!=|=|>|<)\s*(.+)$/',
            trim($expression),
            $matches
        )) {
            [, $stepId, $field, $operator, $rawValue] = $matches;

            $lookupStepId = str_replace('_', '', $stepId);

            // lookup ke normalizedSteps yang key-nya juga sudah tanpa underscore
            $actual = $normalizedSteps[$lookupStepId]['output'][$field] ?? null;

            return self::compare($actual, $operator, self::castValue(trim($rawValue)));
        }

        return false;
    }

    private static function castValue(string $value): mixed
    {
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value) === 'true';
        }

        // strip quotes jika ada: "hello" atau 'hello'
        return trim($value, '"\'');
    }

    private static function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=', '==' => $actual == $expected,
            '!='      => $actual != $expected,
            '>'       => $actual > $expected,
            '<'       => $actual < $expected,
            '>='      => $actual >= $expected,
            '<='      => $actual <= $expected,
            default   => false,
        };
    }
}
