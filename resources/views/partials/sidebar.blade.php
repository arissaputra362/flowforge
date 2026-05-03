{{-- Sidebar --}}
<aside class="w-64 fixed inset-y-0 left-0 bg-[#0f172a] border-r border-white/5 flex flex-col z-50">
    <div class="p-6">
        <div class="flex items-center gap-3">
            <div
                class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <span
                class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">FlowForge</span>
        </div>

        {{-- Tenant Switcher --}}
        <div class="mt-8">
            <button
                class="w-full flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all group">
                <div class="flex items-center gap-3 text-left">
                    <div
                        class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold text-xs">
                        AC</div>
                    <div>
                        <p class="text-xs font-bold text-white leading-tight">Acme Corporation</p>
                        <p class="text-[10px] text-slate-500 font-medium">Pro Plan</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-300 transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <nav class="flex-1 px-4 py-4 space-y-8 overflow-y-auto custom-scrollbar">
        {{-- Main --}}
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-4">Main</p>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white {{ request()->is('dashboard*') ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/10' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        {{-- Workflows --}}
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-4">Workflow Engine</p>
            <div class="space-y-1">
                <a href="/workflows"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white {{ request()->is('workflows*') ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/10' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Workflows
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white opacity-50 cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Executions
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white opacity-50 cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Schedules
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white opacity-50 cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Webhooks
                </a>
            </div>
        </div>

        {{-- Management --}}
        <div>
            <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-4">Management</p>
            <div class="space-y-1">
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Users
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-400 font-medium transition-all hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </div>
        </div>
    </nav>

    {{-- User --}}
    <div class="p-4 border-t border-white/5 bg-slate-900/50">
        <div class="flex items-center gap-3">
            <img class="w-10 h-10 rounded-xl object-cover ring-2 ring-indigo-500/20"
                src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff"
                alt="User">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ Auth::user()->tenant->name }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="p-2 text-slate-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
