<?php

namespace App\Services\Workflow;

use Illuminate\Validation\ValidationException;

class DagParser
{
    /**
     * Validate and normalize a workflow DAG definition.
     */
    public function validate(array $definition): array
    {
        $steps = $this->normalizeSteps($definition);

        $this->assertDependenciesExist($steps);
        $this->assertNoCycles($steps);

        $definition['steps'] = array_values($steps);

        return $definition;
    }

    /**
     * Parse a DAG and return validation plus execution metadata.
     *
     * @return array{definition: array<string, mixed>, execution_order: array<int, string>, root_steps: array<int, string>}
     */
    public function parse(array $definition): array
    {
        $steps = $this->normalizeSteps($definition);

        $this->assertDependenciesExist($steps);

        return [
            'definition' => [
                'steps' => array_values($steps),
            ],
            'execution_order' => $this->topologicalSort($steps),
            'root_steps' => $this->getRootSteps($definition),
        ];
    }

    /**
     * Return step ids that have no dependencies.
     *
     * @return array<int, string>
     */
    public function getRootSteps(array $definition): array
    {
        $steps = $this->normalizeSteps($definition);

        // Kumpulkan semua branch target dari condition steps
        $branchTargets = [];
        foreach ($steps as $step) {
            if (($step['type'] ?? '') === 'condition') {
                $branches = $step['branches'] ?? [];
                if (!empty($branches['true']))  $branchTargets[] = $branches['true'];
                if (!empty($branches['false'])) $branchTargets[] = $branches['false'];
            }
        }

        return array_values(array_keys(array_filter(
            $steps,
            fn(array $step): bool =>
                $step['depends_on'] === [] &&
                !in_array($step['id'], $branchTargets) // ← tambah ini
        )));
    }

    /**
     * Return executable step ids whose dependencies are satisfied.
     *
     * @param array<int, string> $completedSteps
     * @return array<int, string>
     */
    public function getNextExecutableSteps(array $definition, array $completedSteps): array
    {
        $steps = $this->normalizeSteps($definition);

        $this->assertDependenciesExist($steps);
        $this->assertNoCycles($steps);

        // Kumpulkan branch targets — tidak boleh di-dispatch oleh dependency resolver
        $branchTargets = [];
        foreach ($steps as $step) {
            if (($step['type'] ?? '') === 'condition') {
                $branches = $step['branches'] ?? [];
                if (!empty($branches['true']))  $branchTargets[] = $branches['true'];
                if (!empty($branches['false'])) $branchTargets[] = $branches['false'];
            }
        }

        $completedLookup = array_fill_keys(array_map('strval', $completedSteps), true);
        $nextExecutable = [];

        foreach ($this->topologicalSort($steps) as $stepId) {
            if (isset($completedLookup[$stepId])) continue;

            // Skip branch target — dikontrol oleh handleConditionBranch
            if (in_array($stepId, $branchTargets)) continue;

            $dependenciesSatisfied = true;
            foreach ($steps[$stepId]['depends_on'] as $dependency) {
                if (!isset($completedLookup[$dependency])) {
                    $dependenciesSatisfied = false;
                    break;
                }
            }

            if ($dependenciesSatisfied) {
                $nextExecutable[] = $stepId;
            }
        }

        return $nextExecutable;
    }

    /**
     * @return array<string, array{id: string, type: string, depends_on: array<int, string>}>
     */
    private function normalizeSteps(array $definition): array
    {
        $steps = $definition['steps'] ?? null;

        if (! is_array($steps) || $steps === []) {
            throw ValidationException::withMessages([
                'definition.steps' => 'The DAG must contain at least one step.',
            ]);
        }

        $normalizedSteps = [];

        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                throw ValidationException::withMessages([
                    "definition.steps.$index" => 'Each step must be an object.',
                ]);
            }

            $stepId = $step['id'] ?? null;
            $stepType = $step['type'] ?? null;
            $dependsOn = $step['depends_on'] ?? [];

            if (! is_string($stepId) || $stepId === '') {
                throw ValidationException::withMessages([
                    "definition.steps.$index.id" => 'Each step requires a non-empty id.',
                ]);
            }

            if (isset($normalizedSteps[$stepId])) {
                throw ValidationException::withMessages([
                    "definition.steps.$index.id" => 'Step ids must be unique.',
                ]);
            }

            if (! is_string($stepType) || $stepType === '') {
                throw ValidationException::withMessages([
                    "definition.steps.$index.type" => 'Each step requires a non-empty type.',
                ]);
            }

            if (! is_array($dependsOn)) {
                throw ValidationException::withMessages([
                    "definition.steps.$index.depends_on" => 'depends_on must be an array.',
                ]);
            }

            $normalizedDependsOn = array_values(array_unique(array_map('strval', $dependsOn)));

            if (in_array($stepId, $normalizedDependsOn, true)) {
                throw ValidationException::withMessages([
                    "definition.steps.$index.depends_on" => 'A step cannot depend on itself.',
                ]);
            }

            $normalizedSteps[$stepId] = [
                'id' => $stepId,
                'type' => $stepType,
                'depends_on' => $normalizedDependsOn,
            ] + array_diff_key($step, array_flip(['id', 'type', 'depends_on']));
        }

        return $normalizedSteps;
    }

    /**
     * @param array<string, array{id: string, type: string, depends_on: array<int, string>}> $steps
     */
    private function assertDependenciesExist(array $steps): void
    {
        foreach ($steps as $stepId => $step) {
            foreach ($step['depends_on'] as $dependency) {
                if (! isset($steps[$dependency])) {
                    throw ValidationException::withMessages([
                        'definition.steps' => "Step [{$stepId}] depends on missing step [{$dependency}].",
                    ]);
                }
            }
        }
    }

    /**
     * @param array<string, array{id: string, type: string, depends_on: array<int, string>}> $steps
     */
    private function assertNoCycles(array $steps): void
    {
        /**
         * @var array<string, int> $state
         */
        $state = [];

        $visit = function (string $stepId) use (&$visit, &$state, $steps): void {
            $currentState = $state[$stepId] ?? 0;

            if ($currentState === 1) {
                throw ValidationException::withMessages([
                    'definition.steps' => 'The DAG contains a dependency cycle.',
                ]);
            }

            if ($currentState === 2) {
                return;
            }

            $state[$stepId] = 1;

            foreach ($steps[$stepId]['depends_on'] as $dependency) {
                $visit($dependency);
            }

            $state[$stepId] = 2;
        };

        foreach (array_keys($steps) as $stepId) {
            $visit($stepId);
        }
    }

    /**
     * @param array<string, array{id: string, type: string, depends_on: array<int, string>}> $steps
     * @return array<int, string>
     */
    private function topologicalSort(array $steps): array
    {
        /**
         * @var array<string, int> $inDegree
         */
        $inDegree = [];

        /**
         * @var array<string, array<int, string>> $adjacency
         */
        $adjacency = [];

        foreach ($steps as $stepId => $step) {
            $inDegree[$stepId] = count($step['depends_on']);
            $adjacency[$stepId] = [];
        }

        foreach ($steps as $stepId => $step) {
            foreach ($step['depends_on'] as $dependency) {
                $adjacency[$dependency][] = $stepId;
            }
        }

        $queue = [];

        foreach ($inDegree as $stepId => $degree) {
            if ($degree === 0) {
                $queue[] = $stepId;
            }
        }

        $order = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $order[] = $current;

            foreach ($adjacency[$current] as $dependent) {
                $inDegree[$dependent]--;

                if ($inDegree[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }

        if (count($order) !== count($steps)) {
            throw ValidationException::withMessages([
                'definition.steps' => 'The DAG contains a dependency cycle.',
            ]);
        }

        return $order;
    }
}
