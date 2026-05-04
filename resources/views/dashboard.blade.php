@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Manage and monitor your FlowForge Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Operational overview of workflows, runs, and execution health.')

@section('header_actions')
    <a href="{{ route('webworkflows.index') }}"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        View Workflows
    </a>
    @hasanyrole('admin|editor')
    <a href="{{ route('webworkflows.create') }}"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        + New Workflow
    </a>
    @endhasanyrole
@endsection

@push('styles')
    <style>
        .dashboard-shell {
            position: relative;
        }

        .dashboard-shell::before,
        .dashboard-shell::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(64px);
            opacity: .45;
            pointer-events: none;
        }

        .dashboard-shell::before {
            width: 280px;
            height: 280px;
            right: -80px;
            top: -120px;
            background: rgba(99, 102, 241, .22);
        }

        .dashboard-shell::after {
            width: 240px;
            height: 240px;
            left: -100px;
            top: 220px;
            background: rgba(14, 165, 233, .14);
        }

        .metric-card,
        .panel-card,
        .flow-card,
        .activity-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            backdrop-filter: blur(16px);
        }

        .metric-card {
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
        }

        .metric-card::after {
            content: '';
            position: absolute;
            inset: auto -20px -20px auto;
            width: 80px;
            height: 80px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, .08), transparent 70%);
        }

        .metric-label,
        .section-kicker {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: #94a3b8;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #f8fafc;
            margin-top: 10px;
        }

        .metric-hint {
            margin-top: 10px;
            font-size: .8rem;
            color: #94a3b8;
        }

        .glass-title {
            color: #f8fafc;
            font-weight: 700;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, .08);
            background: rgba(255, 255, 255, .04);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
        }

        .trend-bar {
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
            overflow: hidden;
        }

        .trend-bar>span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        .workflow-mini {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .workflow-mini span {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            font-size: .75rem;
            color: #cbd5e1;
        }

        .table-row {
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .table-row:last-child {
            border-bottom: 0;
        }

        .activity-item {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: start;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .activity-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
    </style>
@endpush

@section('content')
    @php
        $statusStyles = [
            'running' => ['dot' => 'bg-amber-400', 'badge' => 'badge-running'],
            'pending' => ['dot' => 'bg-slate-400', 'badge' => 'badge-pending'],
            'completed' => ['dot' => 'bg-emerald-400', 'badge' => 'badge-completed'],
            'failed' => ['dot' => 'bg-rose-400', 'badge' => 'badge-failed'],
        ];

        $triggerStyle = [
            'manual' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
            'cron' => 'bg-violet-500/20 text-violet-300 border-violet-500/30',
            'webhook' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
        ];
    @endphp

    <div class="dashboard-shell fade-in space-y-8">
        <div
            class="glass p-6 lg:p-8 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between relative overflow-hidden">
            <div class="max-w-3xl">
                <div class="section-kicker mb-3">Tenant workspace</div>
                <h2 class="text-white font-black text-3xl lg:text-5xl leading-tight">
                    {{ $tenant->name ?? 'FlowForge Dashboard' }}
                </h2>
                <p class="text-slate-400 mt-4 max-w-2xl leading-7">
                    Real-time operational overview for workflows, workflow runs, execution logs, and recent failure
                    patterns.
                </p>

                <div class="flex flex-wrap gap-3 mt-5">
                    <span class="status-chip text-slate-200">
                        <span class="status-dot bg-indigo-400"></span>
                        {{ $summary['workflows_total'] }} workflows
                    </span>
                    <span class="status-chip text-slate-200">
                        <span class="status-dot bg-cyan-400"></span>
                        {{ $summary['runs_total'] }} total runs
                    </span>
                    <span class="status-chip text-slate-200">
                        <span class="status-dot bg-emerald-400"></span>
                        {{ $summary['recent_runs'] }} in last 24h
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 w-full lg:w-auto lg:min-w-[320px]">
                <div class="panel-card p-4">
                    <div class="section-kicker">Users</div>
                    <div class="text-white text-2xl font-bold mt-2">{{ $tenant->users_count ?? 0 }}</div>
                    <div class="text-slate-500 text-sm mt-1">Members in this tenant</div>
                </div>
                <div class="panel-card p-4">
                    <div class="section-kicker">Workflows</div>
                    <div class="text-white text-2xl font-bold mt-2">
                        {{ $tenant->workflows_count ?? $summary['workflows_total'] }}</div>
                    <div class="text-slate-500 text-sm mt-1">Versioned definitions</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="metric-card">
                <div class="metric-label">Active runs</div>
                <div class="metric-value">{{ $summary['active_runs'] }}</div>
                <div class="metric-hint">Runs that are pending or executing right now.</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Success rate</div>
                <div class="metric-value">{{ $summary['success_rate'] }}%</div>
                <div class="metric-hint">Last 24h completed runs.</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Failure rate</div>
                <div class="metric-value">{{ $summary['failure_rate'] }}%</div>
                <div class="metric-hint">Last 24h completed runs.</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Avg execution time</div>
                <div class="metric-value">{{ $summary['avg_duration_label'] }}</div>
                <div class="metric-hint">Based on finished runs in the last 24h.</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="panel-card p-6 xl:col-span-2">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <div class="section-kicker mb-2">Health panel</div>
                        <h3 class="glass-title text-xl">System pulse in the last 24 hours</h3>
                    </div>
                    <span class="text-xs text-slate-500">Live operational snapshot</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flow-card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-slate-500 text-xs uppercase tracking-[0.18em]">Healthy throughput</div>
                                <div class="text-white text-2xl font-bold mt-2">{{ $summary['success_rate'] }}%</div>
                            </div>
                            <span class="badge badge-completed">{{ $summary['completed_runs'] }} completed</span>
                        </div>
                        <div class="trend-bar mt-4">
                            <span class="bg-linear-to-r from-emerald-400 via-cyan-400 to-indigo-500"
                                style="width: {{ $summary['success_rate'] }}%"></span>
                        </div>
                    </div>

                    <div class="flow-card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-slate-500 text-xs uppercase tracking-[0.18em]">Failure pressure</div>
                                <div class="text-white text-2xl font-bold mt-2">{{ $summary['failure_rate'] }}%</div>
                            </div>
                            <span class="badge badge-failed">{{ $summary['failed_runs'] }} failed</span>
                        </div>
                        <div class="trend-bar mt-4">
                            <span class="bg-linear-to-r from-rose-400 to-amber-400"
                                style="width: {{ max(8, $summary['failure_rate']) }}%"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="flow-card p-4">
                        <div class="section-kicker mb-2">Active</div>
                        <div class="text-white text-2xl font-bold">{{ $summary['active_runs'] }}</div>
                        <div class="text-slate-500 text-sm mt-1">Pending and running executions</div>
                    </div>
                    <div class="flow-card p-4">
                        <div class="section-kicker mb-2">Recent activity</div>
                        <div class="text-white text-2xl font-bold">{{ $summary['recent_runs'] }}</div>
                        <div class="text-slate-500 text-sm mt-1">Runs started in the last 24 hours</div>
                    </div>
                    <div class="flow-card p-4">
                        <div class="section-kicker mb-2">Execution logs</div>
                        <div class="text-white text-2xl font-bold">{{ $recentLogs->count() }}</div>
                        <div class="text-slate-500 text-sm mt-1">Latest trace entries</div>
                    </div>
                </div>
            </div>

            <div class="panel-card p-6">
                <div class="section-kicker mb-2">Failure hotspots</div>
                <h3 class="glass-title text-xl">Steps needing attention</h3>

                @if ($failureHotspots->isEmpty())
                    <div
                        class="mt-6 rounded-2xl border border-dashed border-white/10 bg-black/20 p-6 text-slate-500 text-sm">
                        No failed steps in this tenant yet.
                    </div>
                @else
                    <div class="space-y-4 mt-5">
                        @foreach ($failureHotspots as $hotspot)
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <div class="text-white text-sm font-medium truncate">{{ $hotspot->step_id }}</div>
                                    <div class="text-slate-400 text-xs">{{ $hotspot->failures }} failures</div>
                                </div>
                                <div class="trend-bar">
                                    <span class="bg-linear-to-r from-rose-400 to-orange-400"
                                        style="width: {{ min(100, $hotspot->failures * 20) }}%"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-6 rounded-2xl border border-white/8 bg-white/2 p-4">
                    <div class="section-kicker mb-2">Tenant context</div>
                    <div class="text-white font-semibold">{{ $tenant->name ?? 'No tenant loaded' }}</div>
                    <div class="text-slate-500 text-sm mt-1">{{ $tenant->users_count ?? 0 }} users,
                        {{ $tenant->workflows_count ?? 0 }} workflows</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="panel-card p-6 xl:col-span-2">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <div class="section-kicker mb-2">Recent runs</div>
                        <h3 class="glass-title text-xl">Execution history</h3>
                    </div>
                    <span class="text-xs text-slate-500">Latest 6 runs</span>
                </div>

                @if ($recentRuns->isEmpty())
                    <div class="rounded-2xl border border-dashed border-white/10 bg-black/20 p-6 text-slate-500 text-sm">
                        No workflow runs available yet.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($recentRuns as $run)
                            @php
                                $styles = $statusStyles[$run->status] ?? $statusStyles['pending'];
                                $durationSeconds =
                                    $run->updated_at && $run->created_at
                                        ? $run->updated_at->diffInSeconds($run->created_at)
                                        : 0;
                                $durationLabel =
                                    $durationSeconds < 60
                                        ? $durationSeconds . 's'
                                        : (int) floor($durationSeconds / 60) .
                                            'm ' .
                                            str_pad((string) ($durationSeconds % 60), 2, '0', STR_PAD_LEFT) .
                                            's';
                                $progress =
                                    $run->total_steps > 0 ? round(($run->finished_steps / $run->total_steps) * 100) : 0;
                            @endphp

                            <div class="flow-card p-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="status-chip {{ $styles['badge'] }}">
                                                <span class="status-dot {{ $styles['dot'] }}"></span>
                                                {{ $run->status }}
                                            </span>
                                            <span
                                                class="text-xs text-slate-500">v{{ $run->workflowVersion->version ?? '—' }}</span>
                                        </div>
                                        <div class="text-white font-semibold mt-3 truncate">
                                            {{ $run->workflow->name ?? 'Untitled workflow' }}</div>
                                        <div class="text-slate-500 text-sm mt-1">
                                            Started {{ $run->created_at->diffForHumans() }} · Duration
                                            {{ $durationLabel }}
                                        </div>
                                    </div>

                                    <div class="w-full lg:w-72">
                                        <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                                            <span>Step progress</span>
                                            <span>{{ $run->finished_steps }} / {{ $run->total_steps }}</span>
                                        </div>
                                        <div class="trend-bar">
                                            <span class="bg-linear-to-r from-indigo-400 via-cyan-400 to-emerald-400"
                                                style="width: {{ $progress }}%"></span>
                                        </div>
                                    </div>

                                    <a href="/runs/{{ $run->id }}"
                                        class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm hover:bg-white/10 transition whitespace-nowrap">
                                        Open run
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="panel-card p-6">
                <div class="section-kicker mb-2">Recent logs</div>
                <h3 class="glass-title text-xl">Execution activity</h3>

                @if ($recentLogs->isEmpty())
                    <div
                        class="mt-6 rounded-2xl border border-dashed border-white/10 bg-black/20 p-6 text-slate-500 text-sm">
                        No logs captured yet.
                    </div>
                @else
                    <div class="mt-5 space-y-4">
                        @foreach ($recentLogs as $log)
                            <div class="activity-item">
                                <span
                                    class="status-chip {{ $log->level === 'error' ? 'badge-failed' : ($log->level === 'warning' ? 'badge-running' : 'badge-completed') }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                                <div>
                                    <div class="text-white text-sm leading-6">{{ $log->message }}</div>
                                    <div class="text-slate-500 text-xs mt-1">
                                        {{ $log->workflowRun->workflow->name ?? 'Run' }}
                                        @if ($log->stepRun)
                                            · Step {{ $log->stepRun->step_id }}
                                        @endif
                                        · {{ $log->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="panel-card p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <div class="section-kicker mb-2">Workflows</div>
                    <h3 class="glass-title text-xl">Latest definitions</h3>
                </div>
                <a href="/workflows" class="text-sm text-indigo-300 hover:text-indigo-200 transition">See all
                    workflows</a>
            </div>

            @if ($recentWorkflows->isEmpty())
                <div class="rounded-2xl border border-dashed border-white/10 bg-black/20 p-6 text-slate-500 text-sm">
                    No workflows created yet.
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach ($recentWorkflows as $workflow)
                        @php
                            $steps = collect(data_get($workflow->latestVersion, 'definition.steps', []))->take(6);
                            $trigger = $workflow->triggers->first()->type ?? 'manual';
                            $triggerClass = $triggerStyle[$trigger] ?? 'bg-white/10 text-slate-300 border-white/10';
                        @endphp

                        <div class="flow-card p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="px-2 py-1 rounded-full text-[11px] font-semibold border {{ $triggerClass }}">{{ $trigger }}</span>
                                        <span class="text-xs text-slate-500">{{ $workflow->runs_count }} runs</span>
                                    </div>
                                    <div class="text-white font-semibold mt-3 truncate">{{ $workflow->name }}</div>
                                    <div class="text-slate-500 text-sm mt-1 line-clamp-2">
                                        {{ $workflow->description ?: 'No description provided.' }}</div>
                                </div>
                                <a href="/workflows/{{ $workflow->id }}"
                                    class="text-slate-300 hover:text-white transition">View</a>
                            </div>

                            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                                <span>Latest version</span>
                                <span>v{{ $workflow->latestVersion->version ?? '—' }}</span>
                            </div>

                            <div class="workflow-mini mt-3">
                                @forelse($steps as $step)
                                    <span>{{ data_get($step, 'type', 'step') }} ·
                                        {{ data_get($step, 'id', 'node') }}</span>
                                @empty
                                    <span>No DAG steps yet</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
