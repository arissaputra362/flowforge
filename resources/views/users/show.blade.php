@extends('layouts.app')

@section('title', 'User Detail')
@section('page_title', 'User Detail')
@section('page_subtitle', 'Profile, role, tenant, and audit information')

@section('header_actions')
    <a href="{{ route('webusers.edit', $user->id) }}"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        Edit User
    </a>
    <a href="{{ route('webusers.index') }}"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        Back to List
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="glass p-6">
            <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4 min-w-0">
                    <div
                        class="w-14 h-14 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-white text-xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-white font-bold text-xl truncate">{{ $user->name }}</h2>
                        <p class="text-slate-400 text-sm truncate">{{ $user->email }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            @foreach ($user->roles as $role)
                                <span class="badge badge-running">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 w-full md:w-auto md:min-w-80">
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1 uppercase tracking-wide">Created</p>
                        <p class="text-white font-semibold text-sm">{{ $user->created_at?->diffForHumans() ?? '-' }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1 uppercase tracking-wide">Updated</p>
                        <p class="text-white font-semibold text-sm">{{ $user->updated_at?->diffForHumans() ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="glass p-6 xl:col-span-2">
                <div class="mb-5">
                    <h3 class="text-white font-semibold">User Information</h3>
                    <p class="text-slate-500 text-xs mt-1">Main account details for this user.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">User ID</p>
                        <p class="text-white font-mono text-sm break-all">{{ $user->id }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Role</p>
                        <p class="text-white text-sm">{{ $user->getRoleNames()->join(', ') ?: '-' }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Name</p>
                        <p class="text-white text-sm">{{ $user->name }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Email</p>
                        <p class="text-white text-sm break-all">{{ $user->email }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Created By</p>
                        <p class="text-white text-sm">{{ $user->createdBy?->name ?? '-' }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Updated By</p>
                        <p class="text-white text-sm">{{ $user->updatedBy?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="glass p-6">
                <div class="mb-5">
                    <h3 class="text-white font-semibold">Tenant</h3>
                    <p class="text-slate-500 text-xs mt-1">Workspace that owns this user.</p>
                </div>

                @if ($user->tenant)
                    <div class="space-y-3">
                        <div class="stat-card">
                            <p class="text-xs text-slate-500 mb-1">Tenant Name</p>
                            <p class="text-white font-semibold text-sm">{{ $user->tenant->name }}</p>
                        </div>
                        <div class="stat-card">
                            <p class="text-xs text-slate-500 mb-1">Tenant ID</p>
                            <p class="text-white font-mono text-sm break-all">{{ $user->tenant->id }}</p>
                        </div>
                        <div class="stat-card">
                            <p class="text-xs text-slate-500 mb-1">Created</p>
                            <p class="text-white text-sm">{{ $user->tenant->created_at?->diffForHumans() ?? '-' }}</p>
                        </div>
                    </div>
                @else
                    <div
                        class="rounded-xl border border-dashed border-white/10 bg-black/20 p-6 text-center text-slate-500 text-sm">
                        This user is not assigned to a tenant.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
