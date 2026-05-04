<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorkflowService;
use App\Services\ExecutionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WebWorkflowController extends Controller
{
    private WorkflowService $workflowService;
    private ExecutionService $executionService;

    public function __construct(WorkflowService $workflowService, ExecutionService $executionService)
    {
        $this->workflowService = $workflowService;
        $this->executionService = $executionService;
    }

    public function index(): View
    {
        $workflows = $this->workflowService->paginate(null, 15);

        return view('workflows.index', [
            'workflows' => $workflows,
        ]);
    }

    public function create(): View
    {
        return view('workflows.create');
    }

    public function edit(string $id): View
    {
        $workflow = $this->workflowService->findOrFail($id);

        return view('workflows.edit', [
            'workflow' => $workflow,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:manual,cron,webhook',
            'cron_expression' => 'nullable|string',
            'definition' => 'required|json',
        ]);

        $workflow = $this->workflowService->create($request->user(), $data);

        return redirect()->route('workflows.show', $workflow->id);
    }

    public function show(string $id): View
    {
        $workflow = $this->workflowService->findOrFail($id);
        $runs = $workflow->runs()
            ->withCount('stepRuns')
            ->orderByDesc('created_at')
            ->get();

        return view('workflows.show', [
            'workflow' => $workflow,
            'runs' => $runs,
        ]);
    }

    public function run(string $id): View
    {
        $workflow = $this->workflowService->findOrFail($id);
        $workflow->load('latestVersion');

        return view('workflows.run', compact('workflow'));
    }
}
