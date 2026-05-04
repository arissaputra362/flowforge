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
        $stepsOutput = $context['steps'] ?? [];
        $normalizedSteps = [];

        foreach ($stepsOutput as $stepId => $data) {
            $normalizedKey = str_replace('_', '', $stepId);
            $normalizedSteps[$normalizedKey] = $data;
        }

        // Pattern: step.<step_id>.output.<field.nested.path> <op> <value>
        // Contoh: step.step1.output.body.stock > 0
        //         step.step1.output.status == 200
        //         step.step1.output.body.available == true
        if (preg_match(
            '/^step\.(\w+)\.output\.([\w.]+)\s*(>=|<=|==|!=|=|>|<)\s*(.+)$/',
            trim($expression),
            $matches
        )) {
            [, $stepId, $fieldPath, $operator, $rawValue] = $matches;

            $lookupStepId = str_replace('_', '', $stepId);

            $outputData = $normalizedSteps[$lookupStepId]['output'] ?? null;

            // Traverse nested path: "body.stock" → $output['body']['stock']
            $actual = self::getNestedValue($outputData, $fieldPath);

            return self::compare($actual, $operator, self::castValue(trim($rawValue)));
        }

        return false;
    }

    /**
     * Ambil nilai nested dari array menggunakan dot notation.
     * Contoh: getNestedValue(['body' => ['stock' => 5]], 'body.stock') → 5
     */
    private static function getNestedValue(mixed $data, string $path): mixed
    {
        $keys = explode('.', $path);

        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return null;
            }
        }

        return $data;
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
