<?php

namespace App\Http\Controllers;

use App\Repositories\WorkflowRepository;
use App\Services\ExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WorkflowRepository $workflowRepository,
        private readonly ExecutionService $executionService,
    ) {
    }

    public function __invoke(Request $request, string $token): JsonResponse
    {
        $trigger = $this->workflowRepository->findEnabledWebhookTriggerByToken($token);

        if (! $trigger || ! $trigger->workflow) {
            abort(404);
        }

        $input = [
            'payload' => $request->json()->all(),
            'headers' => collect($request->headers->all())
                ->map(fn (array $values) => $values[0] ?? null)
                ->all(),
            'received_at' => now()->toIso8601String(),
        ];

        $run = $this->executionService->startWorkflow($trigger->workflow_id, $input);

        return response()->json([
            'message' => 'Workflow triggered',
            'workflow_id' => $trigger->workflow_id,
            'run_id' => $run->id,
        ]);
    }
}
