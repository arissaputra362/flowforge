@extends('layouts.app')

@section('title', 'Run Monitor')
@section('meta_description', 'Realtime monitoring for workflow run')

@push('styles')
<style>
    .step-card {
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 14px;
        padding: 20px;
        transition: all .4s cubic-bezier(.4,0,.2,1);
        position: relative;
        overflow: hidden;
    }
    .step-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 3px; border-radius: 14px 14px 0 0;
        transition: background .4s;
    }
    .step-card.status-pending::before  { background: #475569; }
    .step-card.status-running::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .step-card.status-success::before  { background: linear-gradient(90deg, #22c55e, #4ade80); }
    .step-card.status-failed::before   { background: linear-gradient(90deg, #ef4444, #f87171); }

    .step-card.status-running {
        border-color: rgba(250,204,21,.2);
        box-shadow: 0 0 20px rgba(250,204,21,.06);
    }
    .step-card.status-success {
        border-color: rgba(34,197,94,.15);
    }
    .step-card.status-failed {
        border-color: rgba(239,68,68,.2);
        box-shadow: 0 0 20px rgba(239,68,68,.06);
    }

    .step-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .step-name { font-weight: 700; font-size: 1rem; color: #e2e8f0; }
    .step-meta { display: flex; gap: 16px; font-size: .75rem; color: #64748b; }

    .ai-panel {
        margin-top: 14px; padding: 16px;
        border-radius: 10px;
        background: rgba(239,68,68,.06);
        border: 1px solid rgba(239,68,68,.15);
        display: none;
        animation: slideDown .3s ease-out;
    }
    .ai-panel.visible { display: block; }
    .ai-panel-title {
        font-size: .75rem; font-weight: 700;
        color: #f87171; text-transform: uppercase;
        letter-spacing: .06em; margin-bottom: 10px;
        display: flex; align-items: center; gap: 6px;
    }
    .ai-field { margin-bottom: 8px; }
    .ai-label { font-size: .7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; }
    .ai-value { font-size: .85rem; color: #e2e8f0; margin-top: 2px; }

    .log-panel {
        margin-top: 12px;
        max-height: 180px; overflow-y: auto;
        font-size: .75rem; font-family: monospace;
        color: #94a3b8;
        padding: 10px;
        background: rgba(0,0,0,.2);
        border-radius: 8px;
        display: none;
    }
    .log-panel.visible { display: block; }
    .log-entry { padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,.03); }
    .log-entry .log-level-info  { color: #60a5fa; }
    .log-entry .log-level-error { color: #f87171; }
    .log-entry .log-level-warning { color: #fbbf24; }

    .error-msg {
        margin-top: 10px; padding: 10px 14px;
        background: rgba(239,68,68,.08);
        border-left: 3px solid #ef4444;
        border-radius: 0 8px 8px 0;
        font-size: .8rem; color: #fca5a5;
        font-family: monospace;
        display: none;
    }
    .error-msg.visible { display: block; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .workflow-progress {
        height: 6px; border-radius: 3px;
        background: rgba(255,255,255,.06);
        overflow: hidden; margin-bottom: 24px;
    }
    .workflow-progress-bar {
        height: 100%; border-radius: 3px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa);
        transition: width .6s cubic-bezier(.4,0,.2,1);
    }

    .run-status-bar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-radius: 12px;
        margin-bottom: 24px;
        background: rgba(99,102,241,.06);
        border: 1px solid rgba(99,102,241,.12);
    }
</style>
@endpush

@section('content')
<div class="fade-in">
    {{-- Breadcrumb --}}
    <div style="margin-bottom:8px;">
        <a href="/workflows/{{ $run->workflow_id }}" style="color:#64748b;text-decoration:none;font-size:.8rem;">← Back to Workflow</a>
    </div>

    <div class="page-header">
        <h1>Run Monitor</h1>
        <span id="run-status" class="badge badge-{{ $run->status }}">{{ $run->status }}</span>
    </div>

    {{-- Run Info --}}
    <div class="run-status-bar">
        <div>
            <span class="text-xs text-muted">Run ID</span>
            <div style="font-family:monospace;font-size:.85rem;color:#a5b4fc;">{{ $run->id }}</div>
        </div>
        <div>
            <span class="text-xs text-muted">Workflow</span>
            <div style="font-size:.85rem;color:#e2e8f0;">{{ $run->workflow->name ?? '—' }}</div>
        </div>
        <div>
            <span class="text-xs text-muted">Started</span>
            <div style="font-size:.85rem;color:#e2e8f0;">{{ $run->created_at->format('M d, H:i:s') }}</div>
        </div>
        <div>
            <span class="text-xs text-muted">Steps</span>
            <div style="font-size:.85rem;color:#e2e8f0;" id="step-progress-text">
                {{ $stepRuns->where('status', 'success')->count() }} / {{ $stepRuns->count() }}
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="workflow-progress">
        @php
            $total = $stepRuns->count();
            $done = $stepRuns->whereIn('status', ['success', 'failed'])->count();
            $pct = $total > 0 ? round($done / $total * 100) : 0;
        @endphp
        <div class="workflow-progress-bar" id="progress-bar" style="width:{{ $pct }}%"></div>
    </div>

    {{-- Step Cards --}}
    <div style="display:grid;gap:16px;" id="step-list">
        @foreach($stepRuns as $step)
        <div class="step-card status-{{ $step->status }}" id="step-{{ $step->step_id }}" data-step-run-id="{{ $step->id }}">
            <div class="step-header">
                <div class="step-name">{{ $step->step_id }}</div>
                <span class="badge badge-{{ $step->status }}" id="badge-{{ $step->step_id }}">{{ $step->status }}</span>
            </div>
            <div class="step-meta">
                <span>Attempt: <strong id="attempt-{{ $step->step_id }}">{{ $step->attempt }}</strong></span>
                @if($step->started_at)
                <span>Started: {{ $step->started_at->format('H:i:s') }}</span>
                @endif
                @if($step->finished_at)
                <span>Finished: {{ $step->finished_at->format('H:i:s') }}</span>
                @endif
            </div>

            {{-- Error Message --}}
            <div class="error-msg {{ $step->last_error ? 'visible' : '' }}" id="error-{{ $step->step_id }}">
                {{ $step->last_error }}
            </div>

            {{-- AI Analysis Panel --}}
            <div class="ai-panel {{ $step->ai_analysis ? 'visible' : '' }}" id="ai-{{ $step->step_id }}">
                <div class="ai-panel-title">
                    🤖 AI Failure Analysis
                </div>
                @if($step->ai_analysis)
                <div class="ai-field">
                    <div class="ai-label">Root Cause</div>
                    <div class="ai-value" id="ai-root-{{ $step->step_id }}">{{ $step->ai_analysis['root_cause'] ?? '' }}</div>
                </div>
                <div class="ai-field">
                    <div class="ai-label">Suggestion</div>
                    <div class="ai-value" id="ai-suggestion-{{ $step->step_id }}">{{ $step->ai_analysis['suggestion'] ?? '' }}</div>
                </div>
                <div style="display:flex;gap:12px;margin-top:8px;">
                    <div>
                        <div class="ai-label">Severity</div>
                        <span class="badge badge-failed" id="ai-severity-{{ $step->step_id }}" style="margin-top:4px;">
                            {{ $step->ai_analysis['severity'] ?? '—' }}
                        </span>
                    </div>
                    <div>
                        <div class="ai-label">Retry Safe</div>
                        <span class="badge {{ ($step->ai_analysis['retry_safe'] ?? false) ? 'badge-success' : 'badge-failed' }}" id="ai-retry-{{ $step->step_id }}" style="margin-top:4px;">
                            {{ ($step->ai_analysis['retry_safe'] ?? false) ? 'Yes' : 'No' }}
                        </span>
                    </div>
                </div>
                @else
                <div id="ai-content-{{ $step->step_id }}"></div>
                @endif
            </div>

            {{-- Execution Logs --}}
            <div class="log-panel {{ $step->executionLogs->isNotEmpty() ? 'visible' : '' }}" id="logs-{{ $step->step_id }}">
                @foreach($step->executionLogs as $log)
                <div class="log-entry">
                    <span class="log-level-{{ $log->level }}">{{ strtoupper($log->level) }}</span>
                    {{ $log->message }}
                    <span style="color:#475569;">{{ $log->created_at->format('H:i:s') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    // window.Echo is already initialized by resources/js/echo.js via Vite
    // Wait briefly for Echo to be ready
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
    let completedSteps = {{ $stepRuns->whereIn('status', ['success', 'failed'])->count() }};

    function updateProgress() {
        const pct = totalSteps > 0 ? Math.round(completedSteps / totalSteps * 100) : 0;
        document.getElementById('progress-bar').style.width = pct + '%';
    }

    function setStepStatus(stepId, status, attempt) {
        const card = document.getElementById('step-' + stepId);
        const badge = document.getElementById('badge-' + stepId);
        const attemptEl = document.getElementById('attempt-' + stepId);

        if (!card) return;

        // Remove old status classes
        card.className = card.className.replace(/status-\w+/g, '');
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

    function showAI(stepId, analysis) {
        const panel = document.getElementById('ai-' + stepId);
        if (!panel || !analysis) return;

        // Build AI content if not pre-rendered
        const content = document.getElementById('ai-content-' + stepId);
        if (content) {
            content.innerHTML = `
                <div class="ai-field">
                    <div class="ai-label">Root Cause</div>
                    <div class="ai-value">${analysis.root_cause || 'Unknown'}</div>
                </div>
                <div class="ai-field">
                    <div class="ai-label">Suggestion</div>
                    <div class="ai-value">${analysis.suggestion || 'Check logs manually'}</div>
                </div>
                <div style="display:flex;gap:12px;margin-top:8px;">
                    <div>
                        <div class="ai-label">Severity</div>
                        <span class="badge badge-failed" style="margin-top:4px;">${analysis.severity || 'medium'}</span>
                    </div>
                    <div>
                        <div class="ai-label">Retry Safe</div>
                        <span class="badge ${analysis.retry_safe ? 'badge-success' : 'badge-failed'}" style="margin-top:4px;">${analysis.retry_safe ? 'Yes' : 'No'}</span>
                    </div>
                </div>
            `;
        }
        panel.classList.add('visible');
    }

    function addLog(stepId, level, message) {
        const panel = document.getElementById('logs-' + stepId);
        if (!panel) return;
        panel.classList.add('visible');

        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.innerHTML = `<span class="log-level-${level}">${level.toUpperCase()}</span> ${message} <span style="color:#475569;">${new Date().toLocaleTimeString()}</span>`;
        panel.appendChild(entry);
        panel.scrollTop = panel.scrollHeight;
    }

    function updateRunStatus(status) {
        const el = document.getElementById('run-status');
        if (el) {
            el.className = 'badge badge-' + status;
            el.textContent = status;
        }
    }

    function updateStepProgressText() {
        const el = document.getElementById('step-progress-text');
        if (el) {
            const success = document.querySelectorAll('.step-card.status-success').length;
            el.textContent = success + ' / ' + totalSteps;
        }
    }

    // Subscribe to workflow channel
    echo.channel('workflow.' + runId)
        .listen('.App\\Events\\StepStarted', (e) => {
            console.log('StepStarted', e);
            setStepStatus(e.step_id, 'running', e.attempt);
            addLog(e.step_id, 'info', 'Step started (attempt ' + e.attempt + ')');
        })
        .listen('.App\\Events\\StepSucceeded', (e) => {
            console.log('StepSucceeded', e);
            setStepStatus(e.step_id, 'success', e.attempt);
            addLog(e.step_id, 'info', 'Step succeeded');
            completedSteps++;
            updateProgress();
            updateStepProgressText();
        })
        .listen('.App\\Events\\StepFailed', (e) => {
            console.log('StepFailed', e);
            setStepStatus(e.step_id, 'failed', e.attempt);
            addLog(e.step_id, 'error', 'Step failed: ' + (e.error || 'Unknown'));
            showError(e.step_id, e.error);
            completedSteps++;
            updateProgress();
            updateStepProgressText();

            if (e.ai_analysis) {
                showAI(e.step_id, e.ai_analysis);
            }
        })
        .listen('.App\\Events\\WorkflowCompleted', (e) => {
            console.log('WorkflowCompleted', e);
            updateRunStatus('completed');
        });
    }); // end waitForEcho
</script>
@endpush
