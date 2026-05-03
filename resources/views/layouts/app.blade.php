<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FlowForge') — FlowForge</title>
    <meta name="description" content="@yield('meta_description', 'FlowForge — Workflow Orchestration Engine')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <style id="datatable-dark-theme">
        /* Dark theme override */
        .dataTables_wrapper {
            color: #cbd5f5;
        }

        .dataTables_wrapper .dataTables_filter input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 10px;
            padding: 6px 10px;
        }

        .dataTables_wrapper .dataTables_length select {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-radius: 8px;
        }

        table.dataTable tbody tr {
            background: transparent;
        }

        table.dataTable tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .dataTables_paginate .paginate_button {
            color: #94a3b8 !important;
        }

        /* Control (input area) */
        .ts-control {
            background-color: #0b0f1a !important;
            color: #e2e8f0 !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Text di dalam input */
        .ts-control input {
            color: #e2e8f0 !important;
        }

        /* Placeholder */
        .ts-control input::placeholder {
            color: #64748b !important;
        }

        /* Dropdown */
        .ts-dropdown {
            background-color: #0b0f1a !important;
            color: #e2e8f0 !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Option */
        .ts-dropdown .option {
            color: #e2e8f0;
        }

        /* Hover / active */
        .ts-dropdown .option.active {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
        }

        /* Selected item */
        .ts-control .item {
            color: #e2e8f0;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-[#0b0f1a] text-slate-200 font-sans">
    <div class="flex min-h-screen">

        @if (\Auth::check())
            @include('partials.sidebar')

            {{-- Main Content --}}
            <main class="flex-1 ml-64 p-10 min-h-screen">
                <header class="flex items-center justify-between mb-10">
                    <div>
                        <h1 class="text-3xl font-extrabold text-white tracking-tight">@yield('page_title', 'Dashboard')</h1>
                        <p class="text-slate-500 mt-1 font-medium">@yield('page_subtitle', 'Welcome back to FlowForge.')</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button
                            class="p-2.5 rounded-xl bg-white/5 border border-white/5 text-slate-400 hover:text-white transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                        @yield('header_actions')
                    </div>
                </header>

                <div class="fade-in">
                    @yield('content')
                </div>
            </main>
        @else
            {{-- Main Content --}}
            <main class="flex-1 p-10 min-h-screen">
                @yield('content')
            </main>
        @endif
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js"
        integrity="sha512-6BTOlkauINO65nLhXhthZMtepgJSghyimIalb+crKRPhvhmsCdnIuGcVbR5/aQY2A+260iC1OPy1oCdB6pSSwQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    @stack('scripts')
</body>

</html>
