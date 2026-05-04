<?php

namespace App\Services;

use App\Models\WorkflowRun;
use App\Repositories\WorkflowRunRepository;

class WorkflowRunService
{
    private WorkflowRunRepository $repository;

    public function __construct(WorkflowRunRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findWithDetails(string $id): WorkflowRun
    {
        return $this->repository->findWithDetails($id);
    }
}
