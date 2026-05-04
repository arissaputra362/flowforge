@extends('layouts.app')

@section('title', 'Run Monitor')
@section('meta_description', 'Realtime monitoring for workflow run')
@section('page_title', 'Run Monitor')
@section('page_subtitle', 'Trigger and monitor execution in real time.')

@section('header_actions')
    <a href="/workflows/{{ $run->workflow_id }}"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        Back to Detail
    </a>
@endsection

@push('styles')
    <style>
        .step-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 16px;
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
            transition: all .25s ease;
        }

        .step-card::before {
            content: '';
            position: absolute;
            inset: 0 auto auto 0;
            width: 3px;
            height: 100%;
            background: rgba(255, 255, 255, .08);
        }

        .step-card.status-running {
            border-color: rgba(99, 102, 241, .25);
        }

        .step-card.status-success::before {
            background: linear-gradient(180deg, #22c55e, #4ade80);
        }

        .step-card.status-running::before {
            background: linear-gradient(180deg, #f59e0b, #fbbf24);
        }

        .step-card.status-failed::before {
            background: linear-gradient(180deg, #ef4444, #f87171);
        }

        .step-card.status-skipped::before {
            background: linear-gradient(180deg, #64748b, #94a3b8);
        }

        .step-card.status-pending::before {
            background: linear-gradient(180deg, #475569, #64748b);
        }

        .step-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .step-name {
            font-weight: 700;
            color: #f8fafc;
            font-size: 1rem;
        }

        .step-sub {
            margin-top: 4px;
            font-size: 12px;
            color: #94a3b8;
        }

        .step-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: .75rem;
            color: #94a3b8;
            margin-bottom: 14px;
        }

        .step-panel {
            margin-top: 14px;
            padding: 14px;
            border-radius: 12px;
            background: rgba(0, 0, 0, .18);
            border: 1px solid rgba(255, 255, 255, .06);
        }

        .log-panel {
            max-height: 180px;
            overflow-y: auto;
            font-size: .75rem;
            font-family: monospace;
            color: #94a3b8;
            display: none;
        }

        .log-panel.visible {
            display: block;
        }

        .log-entry {
            padding: 3px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
        }

        .log-entry .log-level-info {
            color: #60a5fa;
        }

        .log-entry .log-level-error {
            color: #f87171;
        }

        .error-msg {
            margin-top: 10px;
            padding: 10px 14px;
            background: rgba(239, 68, 68, .08);
            border-left: 3px solid #ef4444;
            border-radius: 0 8px 8px 0;
            font-size: .8rem;
            color: #fca5a5;
            font-family: monospace;
            display: none;
        }

        .error-msg.visible {
            display: block;
        }

        .workflow-progress {
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, .06);
            overflow: hidden;
        }

        .workflow-progress-bar {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa);
            transition: width .35s ease;
        }

        .stat-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 14px;
            padding: 16px 20px;
        }

        .section-title {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            margin-bottom: 8px;
        }
    </style>
@endpush

@section('content')
    @php
        $workflow = $run->workflow;
        $workflowSteps = collect($workflowSteps ?? []);
        $stepMap = $workflowSteps->keyBy('id');
        $triggerType = $workflow->triggers->first()->type ?? 'manual';
        $totalSteps = $stepRuns->count();
        $completedSteps = $stepRuns->whereIn('status', ['success', 'failed', 'skipped'])->count();
        $runningSteps = $stepRuns->where('status', 'running')->count();
        $pendingSteps = $stepRuns->where('status', 'pending')->count();
        $progressPct = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
        $typeIcons = [
            'http' => '🌐',
            'delay' => '⏱',
            'condition' => '◆',
            'script' => '⚡',
        ];
    @endphp

    <div class="space-y-8 fade-in">
        <div class="glass p-6 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div
                    class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xl">
                    ▶</div>
                <div>
                    <h2 class="text-white font-bold text-xl">{{ $workflow->name ?? 'Workflow Run' }}</h2>
                    <p class="text-slate-500 text-sm mt-1 max-w-2xl">Monitor ordered step execution, logs, and failure
                        analysis in one place.</p>
                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <span id="run-status" class="badge badge-{{ $run->status }}">{{ $run->status }}</span>
                        <span
                            class="badge badge-{{ $triggerType === 'manual' ? 'pending' : 'running' }}">{{ $triggerType }}</span>
                        <span class="badge badge-completed">{{ $completedSteps }} completed</span>
                        <span class="badge badge-pending">{{ $pendingSteps }} pending</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 min-w-0 lg:min-w-90">
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1 uppercase tracking-wide">Run ID</p>
                    <p class="text-white font-mono text-sm truncate">{{ $run->id }}</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1 uppercase tracking-wide">Workflow</p>
                    <p class="text-white text-sm truncate">{{ $workflow->name ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="section-title">Started</p>
                <p class="text-white font-semibold text-sm">{{ $run->created_at->format('M d, H:i:s') }}</p>
            </div>
            <div class="stat-card">
                <p class="section-title">Finished</p>
                <p class="text-white font-semibold text-sm">{{ $run->updated_at->format('M d, H:i:s') }}</p>
            </div>
            <div class="stat-card">
                <p class="section-title">Completed</p>
                <p id="step-progress-text" class="text-white font-semibold text-sm">{{ $completedSteps }} /
                    {{ $totalSteps }}</p>
            </div>
            <div class="stat-card">
                <p class="section-title">Running</p>
                <p class="text-white font-semibold text-sm">{{ $runningSteps }}</p>
            </div>
        </div>

        <div class="glass p-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-white font-semibold">Execution Progress</h3>
                    <p class="text-slate-500 text-xs mt-1">Ordered using the latest workflow version definition.</p>
                </div>
                <span class="text-xs text-slate-500">{{ $progressPct }}%</span>
            </div>
            <div class="workflow-progress">
                <div class="workflow-progress-bar" id="progress-bar" style="width:{{ $progressPct }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="glass p-6 xl:col-span-2">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-white font-semibold">Ordered Step Runs</h3>
                        <p class="text-slate-500 text-xs mt-1">This list follows the workflow definition order, not insert
                            time.</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ $totalSteps }} steps</span>
                </div>

                @if ($stepRuns->isEmpty())
                    <div class="p-8 text-center text-slate-500 rounded-xl border border-dashed border-white/10 bg-black/20">
                        No step runs recorded yet.</div>
                @else
                    <div class="space-y-4" id="step-list">
                        @foreach ($stepRuns as $step)
                            @php
                                $stepType = data_get($stepMap->get($step->step_id), 'type', 'unknown');
                                $stepIcon = $typeIcons[$stepType] ?? '●';
                                $stepOutput = $step->output
                                    ? json_encode($step->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                    : null;
                            @endphp

                            <div class="step-card status-{{ $step->status }}" id="step-{{ $step->step_id }}">
                                <div class="step-header">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ $stepIcon }}</span>
                                            <div class="step-name">{{ $step->step_id }}</div>
                                        </div>
                                        <div class="step-sub">Step #{{ $loop->iteration }} • {{ $stepType }}</div>
                                    </div>
                                    <span class="badge badge-{{ $step->status }}"
                                        id="badge-{{ $step->step_id }}">{{ $step->status }}</span>
                                </div>

                                <div class="step-meta">
                                    <span>Attempt: <strong
                                            id="attempt-{{ $step->step_id }}">{{ $step->attempt }}</strong></span>
                                    @if ($step->started_at)
                                        <span>Started: {{ $step->started_at->format('H:i:s') }}</span>
                                    @endif
                                    @if ($step->finished_at)
                                        <span>Finished: {{ $step->finished_at->format('H:i:s') }}</span>
                                    @endif
                                </div>

                                @if ($stepOutput)
                                    <div class="step-panel overflow-x-auto">
                                        <div class="section-title">Output</div>
                                        <pre class="output-json">{{ $stepOutput }}</pre>
                                    </div>
                                @endif

                                <div class="error-msg {{ $step->last_error ? 'visible' : '' }}"
                                    id="error-{{ $step->step_id }}">{{ $step->last_error }}</div>

                                <div class="step-panel">
                                    <div class="section-title">Execution Logs</div>
                                    <div class="log-panel {{ $step->executionLogs->isNotEmpty() ? 'visible' : '' }}"
                                        id="logs-{{ $step->step_id }}">
                                        @foreach ($step->executionLogs as $log)
                                            <div class="log-entry">
                                                <span
                                                    class="log-level-{{ $log->level }}">{{ strtoupper($log->level) }}</span>
                                                {{ $log->message }}
                                                <span style="color:#475569;">{{ $log->created_at->format('H:i:s') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="glass p-6">
                    <div class="mb-4">
                        <h3 class="text-white font-semibold">Run Snapshot</h3>
                        <p class="text-slate-500 text-xs mt-1">Quick summary of the current execution.</p>
                    </div>

                    <div class="space-y-3">
                        <div class="stat-card">
                            <p class="section-title">Workflow Version</p>
                            <p class="text-white text-sm">v{{ $run->workflowVersion->version ?? '—' }}</p>
                        </div>
                        <div class="stat-card">
                            <p class="section-title">Status</p>
                            <p id="statStatus" class="text-white text-sm font-semibold">{{ $run->status }}</p>
                        </div>
                        <div class="stat-card">
                            <p class="section-title">Trigger</p>
                            <p class="text-white text-sm">{{ $triggerType }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass p-6">
                    <div class="mb-4">
                        <h3 class="text-white font-semibold">Notes</h3>
                        <p class="text-slate-500 text-xs mt-1">Why this page is structured this way.</p>
                    </div>
                    <div class="space-y-3 text-sm text-slate-400 leading-6">
                        <p>Step cards are ordered by the workflow definition, so the list stays stable even when jobs finish
                            out of sequence.</p>
                        <p>Success and failure details remain attached to each step card so execution details are still easy
                            to scan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        function waitForEcho(callback, retries = 20) {
            if (window.Echo) {
                callback(window.Echo);
            } else if (retries > 0) {
                setTimeout(() => waitForEcho(callback, retries - 1), 100);
            }
        }

        waitForEcho((echo) => {
            const runId = @json($run->id);
            const totalSteps = {{ $stepRuns->count() }};
            let completedSteps = {{ $completedSteps }};

            function updateProgress() {
                const pct = totalSteps > 0 ? Math.round((completedSteps / totalSteps) * 100) : 0;
                const progressBar = document.getElementById('progress-bar');
                if (progressBar) progressBar.style.width = pct + '%';

                const progressText = document.getElementById('step-progress-text');
                if (progressText) progressText.textContent = completedSteps + ' / ' + totalSteps;
            }

            function setStepStatus(stepId, status, attempt) {
                const card = document.getElementById('step-' + stepId);
                const badge = document.getElementById('badge-' + stepId);
                const attemptEl = document.getElementById('attempt-' + stepId);

                if (!card) return;

                card.className = card.className.replace(/status-\w+/g, '').trim();
                card.classList.add('step-card', 'status-' + status);

                if (badge) {
                    badge.className = 'badge badge-' + status;
                    badge.textContent = status;
                }

                if (attemptEl && attempt !== undefined) {
                    attemptEl.textContent = attempt;
                }
            }

            function showError(stepId, error) {
                const el = document.getElementById('error-' + stepId);
                if (el) {
                    el.textContent = error;
                    el.classList.add('visible');
                }
            }

            function addLog(stepId, level, message) {
                const panel = document.getElementById('logs-' + stepId);
                if (!panel) return;

                const entry = document.createElement('div');
                entry.className = 'log-entry';
                entry.innerHTML =
                    `<span class="log-level-${level}">${level.toUpperCase()}</span> ${message} <span style="color:#475569;">${new Date().toLocaleTimeString()}</span>`;
                panel.appendChild(entry);
                panel.classList.add('visible');
                panel.scrollTop = panel.scrollHeight;
            }

            function updateRunStatus(status) {
                const el = document.getElementById('run-status');
                if (el) {
                    el.className = 'badge badge-' + status;
                    el.textContent = status;
                }

                const statStatus = document.getElementById('statStatus');
                if (statStatus) {
                    statStatus.textContent = status;
                }
            }

            echo.channel('workflow.' + runId)
                .listen('.App\\Events\\StepStarted', (e) => {
                    setStepStatus(e.step_id, 'running', e.attempt);
                    addLog(e.step_id, 'info', 'Step started (attempt ' + e.attempt + ')');
                })
                .listen('.App\\Events\\StepSucceeded', (e) => {
                    setStepStatus(e.step_id, 'success', e.attempt);
                    addLog(e.step_id, 'info', 'Step succeeded');
                    completedSteps++;
                    updateProgress();
                })
                .listen('.App\\Events\\StepFailed', (e) => {
                    setStepStatus(e.step_id, 'failed', e.attempt);
                    addLog(e.step_id, 'error', 'Step failed: ' + (e.error || 'Unknown'));
                    showError(e.step_id, e.error);
                    completedSteps++;
                    updateProgress();
                })
                .listen('.App\\Events\\WorkflowCompleted', () => updateRunStatus('completed'))
                .listen('.App\\Events\\WorkflowFailed', () => updateRunStatus('failed'));

            updateProgress();
        });
    </script>
@endpush
