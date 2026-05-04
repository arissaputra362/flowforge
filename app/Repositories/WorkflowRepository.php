<?php

namespace App\Repositories;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Models\Trigger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WorkflowRepository
{
    public function paginate(?string $tenantId = null, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $paginator = Workflow::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->with(['versions'])
            ->withCount('runs')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (Workflow $workflow) => $this->attachLatestVersion($workflow));

        return $paginator;
    }

    public function paginateWithFilters(?string $tenantId, array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $triggerType = $filters['trigger_type'] ?? null;

        $query = Workflow::query()
            ->with(['latestVersion'])
            ->withCount('runs')
            ->when($tenantId, fn ($builder) => $builder->where('tenant_id', $tenantId));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if (! empty($triggerType)) {
            $query->whereHas('triggers', function ($builder) use ($triggerType) {
                $builder->where('type', $triggerType);
            });
        }

        $allowedSorts = ['name', 'created_at', 'updated_at', 'runs_count'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';

        $query->orderBy($sortBy, $sortDir);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->getCollection()->transform(fn (Workflow $workflow) => $this->attachLatestVersion($workflow));

        return $paginator;
    }

    public function query(?string $tenantId = null)
    {
        $query = Workflow::query()->with(['tenant', 'latestVersion'])->withCount('runs');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    public function findOrFail(string $workflowId, ?string $tenantId = null): Workflow
    {
        $query = Workflow::query()->with(['tenant', 'versions', 'triggers']);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $this->attachLatestVersion($query->findOrFail($workflowId));
    }

    public function findWithLatestVersion(string $id): ?Workflow
    {
        return Workflow::with('latestVersion')->find($id);
    }

    public function findEnabledWebhookTriggerByToken(string $token): ?Trigger
    {
        return Trigger::query()
            ->with(['workflow.latestVersion'])
            ->where('type', 'webhook')
            ->where('enabled', true)
            ->get()
            ->first(fn (Trigger $trigger): bool => data_get($trigger->config, 'webhook_token') === $token);
    }

    public function create(array $workflowData, array $definition, string $version = '1', array $triggerData = []): Workflow
    {
        $workflow = Workflow::create($workflowData);

        WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => $version,
            'definition' => $definition,
        ]);

        $this->syncTrigger($workflow, $triggerData);

        return $this->loadWorkflow($workflow);
    }

    public function createRun(Workflow $workflow, $version, array $input = []): WorkflowRun
    {
        return WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'tenant_id' => $workflow->tenant_id,
            'status' => 'running',
            'input' => $input,
        ]);
    }

    public function update(Workflow $workflow, array $workflowData, array $definition, array $triggerData = []): Workflow
    {
        $workflow->fill($workflowData);
        $workflow->save();

        $latestVersion = $workflow->versions()
            ->lockForUpdate()
            ->orderByRaw('CAST(version AS INTEGER) DESC')
            ->value('version');

        $latestVersionNumber = $latestVersion !== null ? (int) $latestVersion : 0;

        WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => (string) ($latestVersionNumber + 1),
            'definition' => $definition,
        ]);

        $this->syncTrigger($workflow, $triggerData);

        return $this->loadWorkflow($workflow);
    }

    private function loadWorkflow(Workflow $workflow): Workflow
    {
        return $this->attachLatestVersion($workflow->fresh()->load(['tenant', 'versions', 'triggers']));
    }

    private function syncTrigger(Workflow $workflow, array $triggerData): void
    {
        if (! isset($triggerData['type'])) {
            return;
        }

        $config = $triggerData['config'] ?? [];
        $existingTrigger = $workflow->triggers()->first();

        if (($triggerData['type'] ?? null) === 'webhook') {
            $config['webhook_token'] = $config['webhook_token']
                ?? data_get($existingTrigger, 'config.webhook_token')
                ?? Str::random(48);
        }

        Trigger::updateOrCreate(
            ['workflow_id' => $workflow->id],
            [
                'type' => $triggerData['type'],
                'config' => ! empty($config) ? $config : null,
                'enabled' => true,
            ]
        );
    }

    private function attachLatestVersion(Workflow $workflow): Workflow
    {
        $latestVersion = $workflow->versions()
            ->orderByRaw('CAST(version AS INTEGER) DESC')
            ->first();

        $workflow->setRelation('latestVersion', $latestVersion);

        return $workflow;
    }
}
