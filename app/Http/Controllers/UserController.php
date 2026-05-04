<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    use AuthorizesRequests;

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $this->authorize('viewAny', User::class);

        if ($request->has('draw') || $request->has('start') || $request->has('length')) {
            $query = $this->userService->baseQuery($user->tenant_id ?? null);

            return DataTables::of($query)
                ->addColumn('role', function ($user) {
                    return $user->getRoleNames()->implode(', ');
                })
                ->filter(function ($query) use ($request) {
                    if ($search = $request->input('search.value')) {
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%")
                                ->orWhereHas('roles', function ($q2) use ($search) {
                                    $q2->where('name', 'ilike', "%{$search}%");
                                });
                        });
                    }
                })
                ->make(true);
        }

        $paginator = $this->userService->paginateWithFilters(
            $user->tenant_id ?? null,
            [
                'per_page' => $request->integer('per_page', 15),
                'page' => $request->integer('page', 1),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_dir' => $request->query('sort_dir', 'desc'),
                'role' => $request->query('role'),
            ]
        );

        return response()->json($paginator);

    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['created_by'] = $request->user()->id;

        $result = $this->userService->create($data);
        if (! $result['is_success']) {
            return response()->json($result['data'], 422);
        }

        return response()->json($result['data'], 201);
    }

    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);
        $user = $this->userService->findForTenant($user->id, $request->user()->tenant_id ?? null);

        return response()->json($user);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $user = $this->userService->findForTenant($user->id, $request->user()->tenant_id ?? null);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $result = $this->userService->update($user, $data);
        if (! $result['is_success']) {
            return response()->json($result['data'], 422);
        }

        return response()->json($result['data']);
    }
}
