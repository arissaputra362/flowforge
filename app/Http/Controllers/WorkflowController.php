<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest;
use App\Models\Workflow;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(private readonly WorkflowService $workflowService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $workflows = $this->workflowService->paginate(
            $request->filled('tenant_id') ? $request->string('tenant_id')->toString() : null,
            $request->integer('per_page', 15),
        );

        return response()->json($workflows);
    }

    public function store(WorkflowRequest $request): JsonResponse
    {
        \Log::debug('Creating workflow with data', ['data' => $request->validated()]);
        $workflow = $this->workflowService->create($request->validated());

        return response()->json($workflow, 201);
    }

    public function show(Request $request, Workflow $workflow): JsonResponse
    {
        $loadedWorkflow = $this->workflowService->findOrFail(
            $workflow->id,
            $request->filled('tenant_id') ? $request->string('tenant_id')->toString() : null,
        );

        return response()->json($loadedWorkflow);
    }

    public function update(WorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $updatedWorkflow = $this->workflowService->update($workflow, $request->validated());

        return response()->json($updatedWorkflow);
    }
}
