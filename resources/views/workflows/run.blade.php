@extends('layouts.app')

@section('title', 'Run Workflow')
@section('page_title', 'Run Workflow')
@section('page_subtitle', 'Trigger and monitor execution in real time.')

@push('styles')
    <style>
        /* ── Timeline ── */
        .timeline-track {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 0;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .timeline-track::-webkit-scrollbar {
            height: 4px;
        }

        .timeline-track::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, .4);
            border-radius: 2px;
        }

        /* connector line between nodes */
        .tl-connector {
            flex-shrink: 0;
            width: 48px;
            height: 2px;
            margin-top: 28px;
            /* align with circle center */
            background: rgba(255, 255, 255, .08);
            position: relative;
            transition: background .4s;
        }

        .tl-connector.active {
            background: #6366f1;
        }

        .tl-connector.success {
            background: #22c55e;
        }

        .tl-connector.skipped {
            background: rgba(255, 255, 255, .15);
        }

        .tl-connector.failed {
            background: #ef4444;
        }

        /* each step node */
        .tl-node {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 120px;
        }

        .tl-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .04);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            position: relative;
            transition: all .4s cubic-bezier(.4, 0, .2, 1);
        }

        .tl-circle.pending {
            border-color: rgba(255, 255, 255, .15);
        }

        .tl-circle.running {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .2), 0 0 16px rgba(99, 102, 241, .3);
            animation: pulse-ring 1.4s ease-out infinite;
        }

        .tl-circle.success {
            border-color: #22c55e;
            background: rgba(34, 197, 94, .1);
            box-shadow: 0 0 12px rgba(34, 197, 94, .25);
        }

        .tl-circle.failed {
            border-color: #ef4444;
            background: rgba(239, 68, 68, .1);
            box-shadow: 0 0 12px rgba(239, 68, 68, .25);
        }

        .tl-circle.skipped {
            border-color: rgba(255, 255, 255, .1);
            background: rgba(255, 255, 255, .02);
            opacity: .5;
        }

        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, .4), 0 0 16px rgba(99, 102, 241, .3);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(99, 102, 241, 0), 0 0 16px rgba(99, 102, 241, .3);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0), 0 0 16px rgba(99, 102, 241, .3);
            }
        }

        .tl-label {
            margin-top: 10px;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-align: center;
            letter-spacing: .03em;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tl-sublabel {
            margin-top: 3px;
            font-size: 10px;
            color: #475569;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .tl-sublabel.running {
            color: #818cf8;
        }

        .tl-sublabel.success {
            color: #4ade80;
        }

        .tl-sublabel.failed {
            color: #f87171;
        }

        .tl-sublabel.skipped {
            color: #475569;
        }

        /* ── Step detail card ── */
        .step-detail-card {
            display: none;
            animation: slide-in .2s ease;
        }

        .step-detail-card.visible {
            display: block;
        }

        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Log stream ── */
        .log-line {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px;
            line-height: 1.8;
            color: #94a3b8;
            animation: fade-up .2s ease;
        }

        .log-line.info {
            color: #94a3b8;
        }

        .log-line.success {
            color: #4ade80;
        }

        .log-line.error {
            color: #f87171;
        }

        .log-line.warn {
            color: #fbbf24;
        }

        .log-line.system {
            color: #6366f1;
        }

        @keyframes fade-up {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Status badge ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .badge-pending {
            background: rgba(255, 255, 255, .06);
            color: #64748b;
        }

        .badge-running {
            background: rgba(99, 102, 241, .15);
            color: #818cf8;
        }

        .badge-success {
            background: rgba(34, 197, 94, .12);
            color: #4ade80;
        }

        .badge-failed {
            background: rgba(239, 68, 68, .12);
            color: #f87171;
        }

        .badge-skipped {
            background: rgba(255, 255, 255, .05);
            color: #475569;
        }

        .badge-completed {
            background: rgba(34, 197, 94, .15);
            color: #4ade80;
        }

        /* ── Run button ── */
        .btn-run {
            position: relative;
            overflow: hidden;
        }

        .btn-run::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .08), transparent);
            transform: translateX(-100%);
            transition: transform .5s;
        }

        .btn-run:hover::before {
            transform: translateX(100%);
        }

        /* ── Stat cards ── */
        .stat-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 14px;
            padding: 16px 20px;
        }

        /* ── Output JSON ── */
        .output-json {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
            color: #7dd3fc;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
@endpush

@section('header_actions')
    <a href="/workflows"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        List Workflows
    </a>
@endsection

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 fade-in">

        {{-- ═══════════════════════════════════════════
         WORKFLOW INFO HEADER
    ═══════════════════════════════════════════ --}}
        <div class="glass p-6 flex items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20
                        flex items-center justify-center text-xl">
                    ⚡
                </div>
                <div>
                    <h2 class="text-white font-bold text-lg">{{ $workflow->name }}</h2>
                    <p class="text-slate-500 text-sm mt-0.5">{{ $workflow->description ?: 'No description.' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="badge badge-{{ $workflow->trigger_type === 'manual' ? 'pending' : 'running' }}">
                    {{ $workflow->trigger_type }}
                </span>
                <span class="text-slate-600 text-xs">v{{ $workflow->latestVersion->version_number ?? 1 }}</span>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
         TRIGGER PANEL
    ═══════════════════════════════════════════ --}}
        <div class="glass p-6 space-y-5" id="triggerPanel">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-white font-semibold">Trigger Execution</h3>
                    <p class="text-slate-500 text-xs mt-1">Provide optional JSON input and launch the workflow.</p>
                </div>
                <span id="workflowStatus" class="badge badge-pending">Idle</span>
            </div>

            {{-- JSON Input --}}
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-2">
                    Input Payload <span class="text-slate-600">(optional JSON)</span>
                </label>
                <textarea id="inputPayload" rows="4"
                    class="w-full bg-black/30 border border-white/8 rounded-xl px-4 py-3
                       text-sky-300 font-mono text-sm resize-none
                       focus:outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20"
                    placeholder='{
  "key": "value"
}'>{}</textarea>
                <p id="jsonError" class="text-red-400 text-xs mt-1 hidden">Invalid JSON</p>
            </div>

            {{-- Run Button --}}
            <div class="flex justify-end">
                <button id="btnRun" onclick="triggerRun()"
                    class="btn-run px-6 py-3 bg-indigo-600 hover:bg-indigo-500 rounded-xl
                       text-white font-semibold text-sm transition-all
                       flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="btnRunLabel">Run Workflow</span>
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
         MONITOR PANEL (hidden until run starts)
    ═══════════════════════════════════════════ --}}
        <div id="monitorPanel" class="space-y-6 hidden">

            {{-- ── Stats row ── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Run ID</p>
                    <p id="statRunId" class="text-white font-mono text-sm font-semibold truncate">—</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <p id="statStatus" class="text-white font-semibold text-sm">—</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Steps</p>
                    <p id="statSteps" class="text-white font-semibold text-sm">—</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs text-slate-500 mb-1">Elapsed</p>
                    <p id="statElapsed" class="text-white font-semibold text-sm font-mono">0s</p>
                </div>
            </div>

            {{-- ── Timeline ── --}}
            <div class="glass p-6 space-y-4">
                <h3 class="text-white font-semibold text-sm">Execution Timeline</h3>
                <div class="timeline-track" id="timelineTrack">
                    {{-- populated by JS --}}
                </div>
            </div>

            {{-- ── Step detail + Log ── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Selected step detail --}}
                <div class="glass p-6 space-y-4">
                    <h3 class="text-white font-semibold text-sm">Step Detail</h3>
                    <div id="stepDetailPlaceholder" class="text-slate-600 text-sm py-6 text-center">
                        Click a step in the timeline to inspect.
                    </div>
                    <div id="stepDetailCard" class="step-detail-card space-y-3">
                        <div class="flex items-center justify-between">
                            <span id="detailStepId" class="text-white font-mono font-bold text-sm"></span>
                            <span id="detailStatus" class="badge"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <p class="text-slate-500 mb-1">Type</p>
                                <p id="detailType" class="text-slate-200 font-semibold uppercase tracking-wide"></p>
                            </div>
                            <div>
                                <p class="text-slate-500 mb-1">Attempt</p>
                                <p id="detailAttempt" class="text-slate-200 font-semibold"></p>
                            </div>
                            <div>
                                <p class="text-slate-500 mb-1">Started</p>
                                <p id="detailStarted" class="text-slate-200 font-mono"></p>
                            </div>
                            <div>
                                <p class="text-slate-500 mb-1">Finished</p>
                                <p id="detailFinished" class="text-slate-200 font-mono"></p>
                            </div>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs mb-2">Output</p>
                            <div class="bg-black/40 rounded-lg p-3 max-h-40 overflow-auto">
                                <pre id="detailOutput" class="output-json">—</pre>
                            </div>
                        </div>
                        <div id="detailErrorBox" class="hidden">
                            <p class="text-slate-500 text-xs mb-2">Error</p>
                            <div class="bg-red-500/8 border border-red-500/20 rounded-lg p-3">
                                <p id="detailError" class="text-red-400 text-xs font-mono break-all whitespace-pre-wrap">
                                </p>
                            </div>
                        </div>
                        <div id="detailAiBox" class="hidden">
                            <p class="text-slate-500 text-xs mb-2">AI Analysis</p>
                            <div class="bg-indigo-500/8 border border-indigo-500/20 rounded-lg p-3">
                                <p id="detailAi" class="text-indigo-300 text-xs leading-relaxed"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live log stream --}}
                <div class="glass p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-white font-semibold text-sm">Live Log</h3>
                        <button onclick="clearLogs()"
                            class="text-slate-600 hover:text-slate-400 text-xs transition-colors">
                            Clear
                        </button>
                    </div>
                    <div id="logStream"
                        class="bg-black/40 rounded-xl p-4 h-64 overflow-y-auto space-y-0.5 font-mono text-xs">
                        <div class="log-line system">▶ Waiting for workflow run…</div>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // ═══════════════════════════════════════════════════════
        //  CONFIG
        // ═══════════════════════════════════════════════════════
        const WORKFLOW_ID = "{{ $workflow->id }}";
        const TOKEN = "{{ session('api_token') }}";
        const API_BASE = "/api";

        // Reverb config dari VITE env
        const REVERB_KEY = "{{ env('VITE_REVERB_APP_KEY', 'local') }}";
        const REVERB_HOST = "{{ env('VITE_REVERB_HOST', 'localhost') }}";
        const REVERB_PORT = {{ env('VITE_REVERB_PORT', 8081) }};
        const REVERB_SCHEME = "{{ env('VITE_REVERB_SCHEME', 'http') }}";

        // ═══════════════════════════════════════════════════════
        //  STATE
        // ═══════════════════════════════════════════════════════
        let currentRunId = null;
        let echoChannel = null;
        let pusherInstance = null;
        let elapsedTimer = null;
        let startTime = null;

        // step states: { stepId: { status, type, attempt, output, error, ai_analysis, started_at, finished_at } }
        let stepStates = {};

        // step order dari DAG definition (untuk render timeline urut)
        let stepOrder = @json(collect($workflow->latestVersion->definition['steps'] ?? [])->map(fn($s) => ['id' => $s['id'], 'type' => $s['type']])->values());

        // ═══════════════════════════════════════════════════════
        //  INIT TIMELINE dari DAG definition
        // ═══════════════════════════════════════════════════════
        function initTimeline() {
            stepOrder.forEach(step => {
                stepStates[step.id] = {
                    status: 'pending',
                    type: step.type,
                    attempt: 0,
                    output: null,
                    error: null,
                    ai_analysis: null,
                    started_at: null,
                    finished_at: null,
                };
            });
            renderTimeline();
        }

        // ═══════════════════════════════════════════════════════
        //  TRIGGER RUN
        // ═══════════════════════════════════════════════════════
        async function triggerRun() {
            // validate JSON
            const raw = document.getElementById('inputPayload').value.trim();
            let payload = {};

            try {
                payload = raw ? JSON.parse(raw) : {};
                document.getElementById('jsonError').classList.add('hidden');
            } catch {
                document.getElementById('jsonError').classList.remove('hidden');
                return;
            }

            setRunning(true);
            appendLog('system', '▶ Triggering workflow…');

            try {
                const res = await fetch(`${API_BASE}/workflows/${WORKFLOW_ID}/run`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + TOKEN,
                    },
                    body: JSON.stringify({
                        input: payload
                    }),
                });

                const data = await res.json();

                if (!res.ok) throw new Error(data.message || 'Failed to start workflow');

                currentRunId = data.run_id ?? data.id;

                // show monitor
                document.getElementById('monitorPanel').classList.remove('hidden');
                document.getElementById('statRunId').textContent = currentRunId;
                setWorkflowStatus('running');

                // init steps
                initTimeline();

                // start elapsed timer
                startTime = Date.now();
                elapsedTimer = setInterval(updateElapsed, 1000);

                // update step count
                document.getElementById('statSteps').textContent =
                    `0 / ${stepOrder.length}`;

                appendLog('system', `✓ Run started — ID: ${currentRunId}`);

                // subscribe websocket
                subscribeToRun(currentRunId);

            } catch (err) {
                appendLog('error', `✗ ${err.message}`);
                setRunning(false);
            }
        }

        // ═══════════════════════════════════════════════════════
        //  WEBSOCKET (Laravel Echo / Pusher)
        // ═══════════════════════════════════════════════════════
        function subscribeToRun(runId) {
            // Pusher JS tetap dipakai karena Reverb kompatibel dengan protokolnya
            pusherInstance = new Pusher(REVERB_KEY, {
                wsHost: REVERB_HOST,
                wsPort: REVERB_PORT,
                wssPort: REVERB_PORT,
                forceTLS: REVERB_SCHEME === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: 'mt1', // wajib ada meski tidak dipakai Reverb
            });

            const channel = pusherInstance.subscribe(`workflow.${runId}`);
            echoChannel = channel;

            appendLog('system', `📡 Connecting to Reverb: ${REVERB_HOST}:${REVERB_PORT}`);

            pusherInstance.connection.bind('connected', () => {
                appendLog('system', `✓ WebSocket connected`);
            });

            pusherInstance.connection.bind('error', err => {
                appendLog('error', `✗ WebSocket error: ${JSON.stringify(err)}`);
            });

            pusherInstance.connection.bind('disconnected', () => {
                appendLog('warn', `⚠ WebSocket disconnected`);
            });

            // ── Step events ──
            channel.bind('App\\Events\\StepStarted', data => {
                updateStep(data.step_id, {
                    status: 'running',
                    attempt: data.attempt
                });
                appendLog('info', `⚙ [${data.step_id}] started (attempt ${data.attempt})`);
            });

            channel.bind('App\\Events\\StepSucceeded', data => {
                updateStep(data.step_id, {
                    status: 'success',
                    output: data.output,
                    finished_at: data.timestamp,
                });
                appendLog('success', `✓ [${data.step_id}] succeeded`);
                updateStepCount();
            });

            channel.bind('App\\Events\\StepFailed', data => {
                updateStep(data.step_id, {
                    status: 'failed',
                    error: data.error,
                    ai_analysis: data.ai_analysis,
                    finished_at: data.timestamp,
                });
                appendLog('error', `✗ [${data.step_id}] failed — ${data.error}`);
                updateStepCount();
            });

            channel.bind('App\\Events\\StepSkipped', data => {
                updateStep(data.step_id, {
                    status: 'skipped',
                    finished_at: data.timestamp
                });
                appendLog('warn', `⊘ [${data.step_id}] skipped`);
                updateStepCount();
            });

            channel.bind('App\\Events\\WorkflowCompleted', () => {
                setWorkflowStatus('completed');
                appendLog('success', `🎉 Workflow completed!`);
                stopTimer();
                setRunning(false);
            });

            channel.bind('App\\Events\\WorkflowFailed', () => {
                setWorkflowStatus('failed');
                appendLog('error', `✗ Workflow failed.`);
                stopTimer();
                setRunning(false); // ← tombol Run aktif lagi
            });
        }

        // ═══════════════════════════════════════════════════════
        //  STATE UPDATES
        // ═══════════════════════════════════════════════════════
        function updateStep(stepId, patch) {
            if (!stepStates[stepId]) stepStates[stepId] = {};
            Object.assign(stepStates[stepId], patch);
            renderTimeline();

            // refresh detail card if this step is selected
            const card = document.getElementById('stepDetailCard');
            if (card.classList.contains('visible') &&
                document.getElementById('detailStepId').textContent === stepId) {
                showStepDetail(stepId);
            }
        }

        function updateStepCount() {
            const done = Object.values(stepStates)
                .filter(s => ['success', 'failed', 'skipped'].includes(s.status)).length;
            document.getElementById('statSteps').textContent =
                `${done} / ${stepOrder.length}`;
        }

        // ═══════════════════════════════════════════════════════
        //  RENDER TIMELINE
        // ═══════════════════════════════════════════════════════
        const STATUS_ICONS = {
            pending: '○',
            running: '◌',
            success: '✓',
            failed: '✕',
            skipped: '⊘',
        };

        const TYPE_ICONS = {
            http: '🌐',
            delay: '⏱',
            condition: '◆',
            script: '⚡',
        };

        function renderTimeline() {
            const track = document.getElementById('timelineTrack');
            track.innerHTML = '';

            stepOrder.forEach((step, idx) => {
                const state = stepStates[step.id] ?? {
                    status: 'pending'
                };
                const status = state.status;
                const icon = TYPE_ICONS[step.type] ?? '●';

                // node
                const node = document.createElement('div');
                node.className = 'tl-node cursor-pointer';
                node.innerHTML = `
            <div class="tl-circle ${status}" title="${step.id}">
                <span>${icon}</span>
            </div>
            <div class="tl-label">${step.id}</div>
            <div class="tl-sublabel ${status}">${status}</div>
        `;
                node.onclick = () => showStepDetail(step.id);
                track.appendChild(node);

                // connector (not after last)
                if (idx < stepOrder.length - 1) {
                    const conn = document.createElement('div');
                    conn.className = `tl-connector ${status}`;
                    track.appendChild(conn);
                }
            });
        }

        // ═══════════════════════════════════════════════════════
        //  STEP DETAIL
        // ═══════════════════════════════════════════════════════
        function showStepDetail(stepId) {
            const state = stepStates[stepId];
            if (!state) return;

            document.getElementById('stepDetailPlaceholder').classList.add('hidden');
            const card = document.getElementById('stepDetailCard');
            card.classList.add('visible');

            document.getElementById('detailStepId').textContent = stepId;
            document.getElementById('detailType').textContent = state.type ?? '—';
            document.getElementById('detailAttempt').textContent = state.attempt ?? '—';
            document.getElementById('detailStarted').textContent =
                state.started_at ? fmtTime(state.started_at) : '—';
            document.getElementById('detailFinished').textContent =
                state.finished_at ? fmtTime(state.finished_at) : '—';
            document.getElementById('detailOutput').textContent =
                state.output ? JSON.stringify(state.output, null, 2) : '—';

            // status badge
            const badgeEl = document.getElementById('detailStatus');
            badgeEl.className = `badge badge-${state.status}`;
            badgeEl.textContent = state.status;

            // error box
            const errBox = document.getElementById('detailErrorBox');
            if (state.error) {
                errBox.classList.remove('hidden');
                document.getElementById('detailError').textContent = state.error;
            } else {
                errBox.classList.add('hidden');
            }

            // AI analysis box
            const aiBox = document.getElementById('detailAiBox');
            if (state.ai_analysis) {
                aiBox.classList.remove('hidden');
                const ai = state.ai_analysis;
                if (typeof ai === 'object' && ai !== null) {
                    document.getElementById('detailAi').innerHTML = `
                        <div class="space-y-2">
                            <div><span class="text-indigo-400 font-semibold">Root Cause:</span>
                                <span class="text-slate-300 ml-1">${ai.root_cause ?? '—'}</span></div>
                            <div><span class="text-indigo-400 font-semibold">Suggestion:</span>
                                <span class="text-slate-300 ml-1">${ai.suggestion ?? '—'}</span></div>
                            <div class="flex gap-4">
                                <div><span class="text-indigo-400 font-semibold">Severity:</span>
                                    <span class="text-slate-300 ml-1">${ai.severity ?? '—'}</span></div>
                                <div><span class="text-indigo-400 font-semibold">Retry Safe:</span>
                                    <span class="text-slate-300 ml-1">${ai.retry_safe ? 'Yes' : 'No'}</span></div>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('detailAi').textContent = ai ?? '—';
                }
            } else {
                aiBox.classList.add('hidden');
            }
        }

        // ═══════════════════════════════════════════════════════
        //  LOG STREAM
        // ═══════════════════════════════════════════════════════
        function appendLog(level, message) {
            const stream = document.getElementById('logStream');
            const now = new Date().toLocaleTimeString('id-ID', {
                hour12: false,
                timeZone: 'Asia/Jakarta', // UTC+7 / WIB
            });
            const line = document.createElement('div');
            line.className = `log-line ${level}`;
            line.textContent = `[${now}] ${message}`;
            stream.appendChild(line);
            stream.scrollTop = stream.scrollHeight;
        }

        function clearLogs() {
            document.getElementById('logStream').innerHTML =
                '<div class="log-line system">▶ Log cleared.</div>';
        }

        // ═══════════════════════════════════════════════════════
        //  HELPERS
        // ═══════════════════════════════════════════════════════
        function setRunning(isRunning) {
            const btn = document.getElementById('btnRun');
            const label = document.getElementById('btnRunLabel');
            btn.disabled = isRunning;
            label.textContent = isRunning ? 'Running…' : 'Run Workflow';
        }

        function setWorkflowStatus(status) {
            const el = document.getElementById('workflowStatus');
            el.className = `badge badge-${status}`;
            el.textContent = status;
            document.getElementById('statStatus').textContent = status;
        }

        function updateElapsed() {
            const s = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('statElapsed').textContent =
                s < 60 ? `${s}s` : `${Math.floor(s/60)}m ${s%60}s`;
        }

        function stopTimer() {
            if (elapsedTimer) {
                clearInterval(elapsedTimer);
                elapsedTimer = null;
            }
        }

        function fmtTime(iso) {
            return new Date(iso).toLocaleTimeString('id-ID', {
                hour12: false
            });
        }
    </script>
@endpush
