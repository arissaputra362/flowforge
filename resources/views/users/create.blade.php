@extends('layouts.app')

@section('title', 'Create User')
@section('page_title', 'Create User')
@section('page_subtitle', 'Add new user to your workspace')

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="glass p-8">

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('webusers.store') }}" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label class="text-sm text-slate-400">Name</label>
                    <input type="text" name="name"
                        class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white">
                </div>

                {{-- Email --}}
                <div>
                    <label class="text-sm text-slate-400">Email</label>
                    <input type="email" name="email"
                        class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white">
                </div>

                {{-- Password --}}
                <div>
                    <label class="text-sm text-slate-400">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="••••••••"
                            class="w-full px-4 py-2.5 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">

                        {{-- Toggle Button --}}
                        <button type="button" onclick="togglePassword('password', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">

                            {{-- Eye Icon --}}
                            <div class="container-eye inline-block">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="container-eye-slash hidden">
                                <i class="fas fa-eye-slash"></i>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Role --}}
                <div>
                    <label class="text-sm text-slate-400">Role</label>
                    <select name="role" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-3 text-white
           [&>option]:bg-slate-800 [&>option]:text-white">
                        <option value="admin">Admin</option>
                        <option value="editor">Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white">
                    Create User
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);

            if (input.type === "password") {
                input.type = "text";
                btn.classList.add("text-indigo-400");
                btn.querySelector(".container-eye").classList.remove("inline-block");
                btn.querySelector(".container-eye").classList.add("hidden");
                btn.querySelector(".container-eye-slash").classList.remove("hidden");
                btn.querySelector(".container-eye-slash").classList.add("inline-block");
            } else {
                input.type = "password";
                btn.classList.remove("text-indigo-400");
                btn.querySelector(".container-eye").classList.remove("hidden");
                btn.querySelector(".container-eye").classList.add("inline-block");
                btn.querySelector(".container-eye-slash").classList.remove("inline-block");
                btn.querySelector(".container-eye-slash").classList.add("hidden");
            }
        }
    </script>
@endpush
