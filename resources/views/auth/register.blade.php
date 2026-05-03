@extends('layouts.app')

@section('title', 'Register')
@section('meta_description', 'Create your FlowForge workspace')

@section('content')
    <div class="flex items-center justify-center min-h-screen fade-in">

        <div class="w-full max-w-xl">

            {{-- Header --}}
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-extrabold text-white tracking-tight">
                    Create your workspace
                </h1>
                <p class="text-slate-400 mt-2 text-sm">
                    Start building workflows with FlowForge
                </p>
            </div>

            {{-- Card --}}
            <div class="glass p-8">

                {{-- Error --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                    @csrf

                    {{-- Company / Tenant --}}
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Company Name</label>
                        <input type="text" name="tenant_name" value="{{ old('tenant_name') }}"
                            placeholder="e.g. FlowForge Inc."
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Password</label>
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

                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">

                            <button type="button" onclick="togglePassword('password_confirmation', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">

                                <div class="container-eye inline-block">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="container-eye-slash hidden">
                                    <i class="fas fa-eye-slash"></i>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Terms --}}
                    {{-- <div class="flex items-center gap-2 text-sm text-slate-400">
                        <input type="checkbox" name="terms" class="rounded bg-white/5 border-white/10">
                        <span>I agree to terms & conditions</span>
                    </div> --}}

                    {{-- Button --}}
                    <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all cursor-pointer">
                        Create Account
                    </button>

                    {{-- Footer --}}
                    <p class="text-center text-sm text-slate-500 mt-4">
                        Already have an account?
                        <a href="/login" class="text-indigo-400 hover:text-indigo-300 font-medium">
                            Login
                        </a>
                    </p>
                </form>
            </div>

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
