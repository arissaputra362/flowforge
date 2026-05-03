<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest;
use App\Models\Workflow;
use App\Services\ExecutionService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class WorkflowController extends Controller
{
    public function __construct(private readonly WorkflowService $workflowService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        // $workflows = $this->workflowService->paginate(
        //     $request->filled('tenant_id') ? $request->string('tenant_id')->toString() : null,
        //     $request->integer('per_page', 15),
        // );

        // return response()->json($workflows);

        $user = $request->user();
        \Log::debug(json_encode($user));

        $query = $this->workflowService->baseQuery(
            $user->tenant_id ?? null,
        );

        return DataTables::of($query)
            ->addColumn('version', fn($w) => optional($w->latestVersion)->version)
            ->addColumn('runs_count', fn($w) => $w->runs()->count())
            ->filter(function ($query) use ($request) {
                if ($search = $request->input('search.value')) {
                    $query->where('name', 'ilike', "%{$search}%");
                }
            })
            ->make(true);
        
    }

    public function store(WorkflowRequest $request): JsonResponse
    {
        \Log::debug('Creating workflow with data', ['data' => $request->validated()]);
        $workflow = $this->workflowService->create($request->user(), $request->validated());

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

    public function trigger(Request $request, Workflow $workflow)
    {
        $run = app(ExecutionService::class)
            ->startWorkflow($workflow->id, $request->input('input', []));

        return response()->json(['run_id' => $run->id]);
    }
}
