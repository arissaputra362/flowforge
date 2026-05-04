<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;

class WebUserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(){
        return view('users.index');
    }

    public function create(){
        return view('users.create');
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $authUser = auth()->user();
        $data['tenant_id'] = $authUser->tenant_id;
        $data['created_by'] = $authUser->id;
        
        $result = $this->userService->create($data);
        if (!$result['is_success']) {
            return back()->withErrors($result['data']['message']);
        }

        return redirect('/users')->with('success', 'User created');
    }

    public function show(User $user)
    {
        $user = $this->userService->findForTenant($user->id, auth()->user()->tenant_id);

        return view('users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        $user = $this->userService->findForTenant($user->id, auth()->user()->tenant_id);

        return view('users.edit', [
            'user' => $user,
            'roles' => ['admin', 'editor', 'viewer'],
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        $user = $this->userService->findForTenant($user->id, auth()->user()->tenant_id);

        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $result = $this->userService->update($user, $data);
        if (! $result['is_success']) {
            return back()->withErrors($result['data']['message'])->withInput();
        }

        return redirect()
            ->route('webusers.show', $result['data']->id)
            ->with('success', 'User updated');
    }
}
