<?php

namespace App\Services;

use App\Models\Workflow;
use App\Services\Workflow\DagParser;
use App\Repositories\WorkflowRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    public function __construct(
        private readonly WorkflowRepository $workflowRepository,
        private readonly DagParser $dagParser,
    ) {
    }

    public function paginate(?string $tenantId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->workflowRepository->paginate($tenantId, $perPage);
    }

    public function baseQuery(?string $tenantId){
        return $this->workflowRepository->query($tenantId);
    }

    public function findOrFail(string $workflowId, ?string $tenantId = null): Workflow
    {
        return $this->workflowRepository->findOrFail($workflowId, $tenantId);
    }

    public function create($user, array $data): Workflow
    {
        return DB::transaction(function () use ($user, $data): Workflow {
            $dag = json_decode($data['definition'], true);
            $definition = $this->dagParser->validate($dag);

            return $this->workflowRepository->create(
                [
                    'tenant_id' => $user->tenant_id,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                ],
                $definition,
                '1',
            );
        });
    }

    public function update(Workflow $workflow, array $data): Workflow
    {
        return DB::transaction(function () use ($workflow, $data): Workflow {
            $definition = $this->dagParser->validate($data['definition']);

            return $this->workflowRepository->update(
                $workflow,
                [
                    'tenant_id' => $data['tenant_id'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                ],
                $definition,
            );
        });
    }


}
