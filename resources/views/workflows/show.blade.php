@extends('layouts.app')

@section('title', $workflow->name)
@section('meta_description', 'Workflow detail: ' . $workflow->name)
@section('page_title', $workflow->name)
@section('page_subtitle', 'Workflow detail, version structure, and execution history.')

@section('header_actions')
    <a href="/workflows/{{ $workflow->id }}/edit"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        Edit Workflow
    </a>
    <a href="/workflows"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        Back to List
    </a>
    <a href="/workflows/{{ $workflow->id }}/run"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        Run Workflow
    </a>
@endsection

@section('content')
<div class="space-y-8 fade-in">
    @php
        $latestVersion = $workflow->latestVersion;
        $definition = $latestVersion->definition ?? [];
        $steps = $definition['steps'] ?? [];
        $currentTrigger = $workflow->triggers->first();
        $triggerType = $currentTrigger->type ?? 'manual';
        $typeIcon = [
            'http' => '🌐',
            'delay' => '⏱',
            'condition' => '◆',
            'script' => '⚡',
        ];
        $typeColor = [
            'http' => 'border-blue-500/40 text-blue-400',
            'delay' => 'border-yellow-500/40 text-yellow-400',
            'condition' => 'border-purple-500/60 text-purple-400',
            'script' => 'border-green-500/40 text-green-400',
        ];
        $branchTargets = [];

        foreach ($steps as $previewStep) {
            if (($previewStep['type'] ?? null) === 'condition') {
                if (!empty($previewStep['branches']['true'])) {
                    $branchTargets[$previewStep['branches']['true']] = true;
                }
                if (!empty($previewStep['branches']['false'])) {
                    $branchTargets[$previewStep['branches']['false']] = true;
                }
            }
        }
    @endphp

    <div class="glass p-6 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xl">
                ⚙
            </div>
            <div>
                <h2 class="text-white font-bold text-xl">{{ $workflow->name }}</h2>
                <p class="text-slate-500 text-sm mt-1 max-w-2xl">
                    {{ $workflow->description ?: 'No description provided for this workflow.' }}
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-4">
                    <span class="badge badge-{{ $triggerType === 'manual' ? 'pending' : 'running' }}">
                        {{ $triggerType }}
                    </span>
                    <span class="badge badge-completed">{{ $workflow->versions->count() }} versions</span>
                    <span class="badge badge-pending">{{ $runs->count() }} runs</span>
                    <span class="text-xs text-slate-500">Latest version: v{{ $latestVersion->version ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 min-w-0 lg:min-w-90">
            <div class="stat-card">
                <p class="text-xs text-slate-500 mb-1 uppercase tracking-wide">Versions</p>
                <p class="text-white font-bold text-2xl">{{ $workflow->versions->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-slate-500 mb-1 uppercase tracking-wide">Total Runs</p>
                <p class="text-white font-bold text-2xl">{{ $runs->count() }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="glass p-6 xl:col-span-2">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-white font-semibold">Latest Version</h3>
                    <p class="text-slate-500 text-xs mt-1">Workflow definition currently used for execution.</p>
                </div>
                <span class="badge badge-running">v{{ $latestVersion->version ?? '—' }}</span>
            </div>

            @if($latestVersion && $definition)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Trigger</p>
                        <p class="text-white font-semibold text-sm uppercase tracking-wide">{{ $triggerType }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Steps</p>
                        <p class="text-white font-semibold text-sm">{{ count($steps) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-slate-500 mb-1">Version ID</p>
                        <p class="text-white font-mono text-sm truncate">{{ $latestVersion->id }}</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-0 min-h-32">
                    @forelse($steps as $step)
                        @if(isset($branchTargets[$step['id'] ?? '']))
                            @continue
                        @endif

                        @if(! $loop->first)
                            <div class="flex justify-center">
                                <div style="width:2px;height:20px;background:rgba(255,255,255,0.15);position:relative;">
                                    <div style="position:absolute;bottom:-4px;left:-3px;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid rgba(255,255,255,0.2);"></div>
                                </div>
                            </div>
                        @endif

                        @php
                            $stepType = $step['type'] ?? 'unknown';
                            $stepIcon = $typeIcon[$stepType] ?? '●';
                            $stepColor = $typeColor[$stepType] ?? 'border-white/20 text-slate-400';
                            $stepId = $step['id'] ?? 'step';
                            $dependsOn = $step['depends_on'] ?? [];
                            $conditionTrue = $stepType === 'condition' ? collect($steps)->firstWhere('id', data_get($step, 'branches.true')) : null;
                            $conditionFalse = $stepType === 'condition' ? collect($steps)->firstWhere('id', data_get($step, 'branches.false')) : null;
                        @endphp

                        <div class="flex flex-col items-center">
                            <div class="border {{ $stepColor }} bg-black/30 rounded-lg px-3 py-2 text-center min-w-30">
                                <div class="text-base">{{ $stepIcon }}</div>
                                <div class="text-xs font-semibold text-white mt-1">{{ $stepId }}</div>
                                <div class="text-xs text-slate-500 uppercase tracking-wide">{{ $stepType }}</div>
                                @if($stepType === 'condition' && !empty($step['config']['expression']))
                                    <div class="text-xs text-purple-400 mt-1 font-mono truncate max-w-30">
                                        {{ $step['config']['expression'] }}
                                    </div>
                                @endif
                            </div>

                            @if($stepType === 'condition' && ($conditionTrue || $conditionFalse))
                                <div class="w-full mt-3">
                                    <svg width="100%" height="40" viewBox="0 0 220 40" preserveAspectRatio="xMidYMid meet">
                                        <line x1="110" y1="0" x2="55" y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3" />
                                        <line x1="110" y1="0" x2="165" y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3" />
                                        <rect x="20" y="12" width="32" height="14" rx="7" fill="rgba(34,197,94,0.15)" />
                                        <text x="36" y="23" text-anchor="middle" font-size="9" fill="#4ade80" font-family="sans-serif">true</text>
                                        <rect x="168" y="12" width="34" height="14" rx="7" fill="rgba(239,68,68,0.15)" />
                                        <text x="185" y="23" text-anchor="middle" font-size="9" fill="#f87171" font-family="sans-serif">false</text>
                                    </svg>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex flex-col items-center">
                                            @if($conditionTrue)
                                                <div class="border {{ $typeColor[$conditionTrue['type'] ?? ''] ?? 'border-white/20 text-slate-400' }} bg-black/30 rounded-lg px-3 py-2 text-center min-w-30">
                                                    <div class="text-base">{{ $typeIcon[$conditionTrue['type'] ?? ''] ?? '●' }}</div>
                                                    <div class="text-xs font-semibold text-white mt-1">{{ $conditionTrue['id'] ?? 'step' }}</div>
                                                    <div class="text-xs text-slate-500 uppercase tracking-wide">{{ $conditionTrue['type'] ?? 'unknown' }}</div>
                                                </div>
                                            @else
                                                <div class="text-xs text-slate-600 text-center">—</div>
                                            @endif
                                        </div>
                                        <div class="flex flex-col items-center">
                                            @if($conditionFalse)
                                                <div class="border {{ $typeColor[$conditionFalse['type'] ?? ''] ?? 'border-white/20 text-slate-400' }} bg-black/30 rounded-lg px-3 py-2 text-center min-w-30">
                                                    <div class="text-base">{{ $typeIcon[$conditionFalse['type'] ?? ''] ?? '●' }}</div>
                                                    <div class="text-xs font-semibold text-white mt-1">{{ $conditionFalse['id'] ?? 'step' }}</div>
                                                    <div class="text-xs text-slate-500 uppercase tracking-wide">{{ $conditionFalse['type'] ?? 'unknown' }}</div>
                                                </div>
                                            @else
                                                <div class="text-xs text-slate-600 text-center">—</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-white/10 bg-black/20 p-6 text-center text-slate-500 text-sm">
                            No steps found in the latest version.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="rounded-xl border border-dashed border-white/10 bg-black/20 p-6 text-center text-slate-500 text-sm">
                    Latest workflow version is not available yet.
                </div>
            @endif
        </div>

        <div class="glass p-6">
            <div class="mb-4">
                <h3 class="text-white font-semibold">Workflow Snapshot</h3>
                <p class="text-slate-500 text-xs mt-1">Quick summary for the current workflow.</p>
            </div>

            <div class="space-y-3">
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Workflow ID</p>
                    <p class="text-white font-mono text-sm break-all">{{ $workflow->id }}</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Created</p>
                    <p class="text-white text-sm">{{ $workflow->created_at?->diffForHumans() ?? '—' }}</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Updated</p>
                    <p class="text-white text-sm">{{ $workflow->updated_at?->diffForHumans() ?? '—' }}</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Latest Run</p>
                    <p class="text-white text-sm">
                        {{ optional($runs->first())->created_at?->diffForHumans() ?? 'No runs yet' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="glass overflow-hidden">
        <div class="p-5 border-b border-white/10 flex items-center justify-between gap-4">
            <div>
                <h3 class="text-white font-semibold">Run History</h3>
                <p class="text-slate-500 text-xs mt-1">Recent executions for this workflow.</p>
            </div>
            <span class="text-xs text-slate-500">{{ $runs->count() }} total</span>
        </div>

        @if($runs->isEmpty())
            <div class="p-8 text-center text-slate-500">
                No runs yet. Use the Run Workflow button to start the first execution.
            </div>
        @else
            <div class="overflow-x-auto">
                <table id="runHistoryTable" class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/10">
                            <th>Run ID</th>
                            <th>Status</th>
                            <th>Steps</th>
                            <th>Started</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($runs as $run)
                            <tr>
                                <td class="text-sm font-mono text-indigo-300">
                                    {{ Str::limit($run->id, 12) }}
                                </td>
                                <td><span class="badge badge-{{ $run->status }}">{{ $run->status }}</span></td>
                                <td class="text-sm">{{ $run->step_runs_count ?? 0 }}</td>
                                <td class="text-muted text-xs" data-order="{{ $run->created_at?->toISOString() }}">{{ $run->created_at->diffForHumans() }}</td>
                                <td style="text-align:right;">
                                    <a href="/runs/{{ $run->id }}"
                                        class="inline-flex items-center px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm transition">
                                        Monitor →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#runHistoryTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']],
                responsive: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Search run history...'
                }
            });
        });
    </script>
@endpush
