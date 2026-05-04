<?php

namespace App\Http\Controllers;

use App\Services\RunMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunMonitorController extends Controller
{
    public function __construct(private readonly RunMonitorService $runMonitorService)
    {
    }

    public function poll(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $since = (int) $request->query('since', 0);

        return response()->json(
            $this->runMonitorService->poll($id, $tenantId, $since)
        );
    }
}
