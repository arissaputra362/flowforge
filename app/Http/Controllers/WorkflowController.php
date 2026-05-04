<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest;
use App\Models\Workflow;
use App\Services\ExecutionService;
use App\Services\WorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class WorkflowController extends Controller
{
    use AuthorizesRequests;
    public function __construct(private readonly WorkflowService $workflowService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::debug(json_encode($user));

        $this->authorize('viewAny', Workflow::class);

        if ($request->has('draw') || $request->has('start') || $request->has('length')) {
            $query = $this->workflowService->baseQuery(
                $user->tenant_id ?? null,
            );

            return DataTables::of($query)
                ->addColumn('version', fn($w) => optional($w->latestVersion)->version)
                ->addColumn('runs_count', fn($w) => $w->runs_count ?? 0)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->input('search.value')) {
                        $query->where('name', 'ilike', "%{$search}%");
                    }
                })
                ->make(true);
        }

        $paginator = $this->workflowService->paginateWithFilters(
            $user->tenant_id ?? null,
            [
                'per_page' => $request->integer('per_page', 15),
                'page' => $request->integer('page', 1),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_dir' => $request->query('sort_dir', 'desc'),
                'trigger_type' => $request->query('trigger_type'),
            ]
        );

        return response()->json($paginator);

    }

    public function store(WorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);
        Log::debug('Creating workflow with data', ['data' => $request->validated()]);
        $workflow = $this->workflowService->create($request->user(), $request->validated());

        return response()->json($workflow, 201);
    }

    public function show(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        $loadedWorkflow = $this->workflowService->findOrFail(
            $workflow->id,
            $request->user()->tenant_id,
        );

        return response()->json($loadedWorkflow);
    }

    public function update(WorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $updatedWorkflow = $this->workflowService->update($workflow, $request->validated());

        return response()->json($updatedWorkflow);
    }

    public function trigger(Request $request, Workflow $workflow)
    {
        $this->authorize('trigger', $workflow);

        $run = app(ExecutionService::class)
            ->startWorkflow($workflow->id, $request->input('input', []));

        return response()->json(['run_id' => $run->id]);
    }
}
