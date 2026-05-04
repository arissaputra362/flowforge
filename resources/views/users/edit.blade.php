@extends('layouts.app')

@section('title', 'Edit User')
@section('page_title', 'Edit User')
@section('page_subtitle', 'Update user information')

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="glass p-8">

            @if ($errors->any())
                <div class="mb-6 text-red-400">
                    @foreach ($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('webusers.update', $user->id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div>
                    <label class="text-sm text-slate-400">Name</label>
                    <input type="text" name="name" value="{{ $user->name }}"
                        class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white">
                </div>

                {{-- Email --}}
                <div>
                    <label class="text-sm text-slate-400">Email</label>
                    <input type="email" name="email" value="{{ $user->email }}"
                        class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white">
                </div>

                {{-- Password (optional) --}}
                <div>
                    <label class="text-sm text-slate-400">Password (optional)</label>
                    <input type="password" name="password"
                        class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white">
                </div>

                {{-- Role --}}
                <div>
                    <label class="text-sm text-slate-400">Role</label>
                    <select name="role"
                        class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white">

                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ $user->hasRole($role) ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white">
                    Update User
                </button>
            </form>
        </div>
    </div>
@endsection
