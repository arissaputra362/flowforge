@extends('layouts.app')

@section('title', $workflow->name)
@section('meta_description', 'Workflow detail: ' . $workflow->name)
@section('page_title', $workflow->name)
@section('page_subtitle', 'Workflow detail, version structure, and execution history.')

@section('header_actions')
    @hasanyrole('admin|editor')
        <a href="/workflows/{{ $workflow->id }}/edit"
            class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
            Edit Workflow
        </a>
    @endhasanyrole
    <a href="/workflows"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        Back to List
    </a>
    <a href="/workflows/{{ $workflow->id }}/run"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        Run Workflow
    </a>
@endsection

@push('styles')
    <style>
        .dag-scroll {
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .dag-canvas {
            min-height: 240px;
        }

        .dag-legend {
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.6);
        }

        .dag-legend-http {
            color: #7dd3fc;
            border-color: rgba(125, 211, 252, 0.4);
        }

        .dag-legend-delay {
            color: #fde68a;
            border-color: rgba(253, 230, 138, 0.4);
        }

        .dag-legend-condition {
            color: #c4b5fd;
            border-color: rgba(196, 181, 253, 0.5);
        }

        .dag-legend-script {
            color: #86efac;
            border-color: rgba(134, 239, 172, 0.4);
        }

        #workflowDag svg {
            width: 100%;
            height: auto;
        }

        #workflowDag .dag-node--selected rect {
            stroke: #fbbf24 !important;
            stroke-width: 3px !important;
        }

        #workflowDag .dag-node--connected rect {
            stroke: #38bdf8 !important;
            stroke-width: 2px !important;
        }

        #workflowDag .dag-node--dim {
            opacity: 0.3;
        }

        #workflowDag .node rect {
            transition: stroke 0.2s ease, opacity 0.2s ease;
        }

        .dag-toggle-btn {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 10px;
            color: #94a3b8;
            transition: all 0.2s ease;
        }

        .dag-toggle-btn:hover {
            color: #e2e8f0;
        }

        .dag-toggle-active {
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
        }
    </style>
@endpush

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

                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="text-xs text-slate-500">Views</div>
                    <div class="inline-flex rounded-xl border border-white/10 bg-black/30 p-1" id="workflowViewToggle">
                        <button class="dag-toggle-btn" data-target="workflowDagCard">DAG</button>
                        <button class="dag-toggle-btn dag-toggle-active" data-target="workflowLinearCard">Linear</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6" id="workflowViewGrid">
                    <div class="rounded-xl border border-white/10 bg-black/20 p-4 hidden" id="workflowDagCard">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div>
                                <h4 class="text-white font-semibold">DAG Graph</h4>
                                <p class="text-slate-500 text-xs mt-1">Dependency map for the latest workflow version.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                <span class="dag-legend dag-legend-http">HTTP</span>
                                <span class="dag-legend dag-legend-delay">Delay</span>
                                <span class="dag-legend dag-legend-condition">Condition</span>
                                <span class="dag-legend dag-legend-script">Script</span>
                            </div>
                        </div>

                        <div class="dag-scroll">
                            <div id="workflowDag" class="mermaid dag-canvas">
                                <div class="text-slate-500 text-xs">Rendering graph...</div>
                            </div>
                        </div>

                        <div id="workflowDagEmpty" class="hidden text-slate-500 text-sm text-center py-4">
                            No steps found in the latest version.
                        </div>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-black/20 p-4" id="workflowLinearCard">
                        <div class="mb-4">
                            <h4 class="text-white font-semibold">Linear Preview</h4>
                            <p class="text-slate-500 text-xs mt-1">Compact representation of step sequence.</p>
                        </div>

                        <div class="flex flex-col items-center gap-0 min-h-32" id="workflowLinearPreview">
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
                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.0/dist/mermaid.min.js"></script>
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

    <script>
        (function() {
            const steps = @json($steps);
            const dagEl = document.getElementById('workflowDag');
            const dagEmptyEl = document.getElementById('workflowDagEmpty');
            const linearEl = document.getElementById('workflowLinearPreview');
            const toggleEl = document.getElementById('workflowViewToggle');

            if (!dagEl || !Array.isArray(steps) || steps.length === 0) {
                if (dagEl) {
                    dagEl.classList.add('hidden');
                }
                if (dagEmptyEl) {
                    dagEmptyEl.classList.remove('hidden');
                }
                return;
            }

            const stepIndex = new Map();
            steps.forEach(step => {
                if (step && step.id) {
                    stepIndex.set(step.id, step);
                }
            });

            const toSafeId = (value) => {
                const base = String(value || 'step');
                const sanitized = base.replace(/[^a-zA-Z0-9_]/g, '_');
                return `s_${sanitized}`;
            };

            const escapeLabel = (value) => {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            const nodeIdByStep = {};
            steps.forEach(step => {
                if (step && step.id) {
                    nodeIdByStep[step.id] = toSafeId(step.id);
                }
            });

            const graphLines = [];
            graphLines.push('flowchart TD');
            graphLines.push('classDef http fill:#0b3b6f,stroke:#7dd3fc,color:#e2e8f0,stroke-width:1.5px;');
            graphLines.push('classDef delay fill:#3b2f00,stroke:#fde68a,color:#fef9c3,stroke-width:1.5px;');
            graphLines.push('classDef condition fill:#2f1b4c,stroke:#c4b5fd,color:#ede9fe,stroke-width:1.5px;');
            graphLines.push('classDef script fill:#123c1c,stroke:#86efac,color:#dcfce7,stroke-width:1.5px;');
            graphLines.push('classDef unknown fill:#0f172a,stroke:#94a3b8,color:#e2e8f0,stroke-width:1.5px;');

            steps.forEach(step => {
                if (!step || !step.id) {
                    return;
                }

                const safeId = nodeIdByStep[step.id];
                const type = step.type || 'unknown';
                const typeKey = String(type).toLowerCase();
                const classKey = ['http', 'delay', 'condition', 'script'].includes(typeKey) ? typeKey : 'unknown';
                const label = `${escapeLabel(step.id)}<br/>${escapeLabel(typeKey)}`;
                graphLines.push(`${safeId}["${label}"]`);
                graphLines.push(`class ${safeId} ${classKey};`);
            });

            const adjacency = {};
            const ensureAdj = (id) => {
                if (!adjacency[id]) {
                    adjacency[id] = { parents: new Set(), children: new Set() };
                }
                return adjacency[id];
            };

            steps.forEach(step => {
                if (!step || !step.id) {
                    return;
                }

                const currentId = step.id;
                const currentSafe = nodeIdByStep[currentId];
                const dependsOn = Array.isArray(step.depends_on) ? step.depends_on : [];

                dependsOn.forEach(parentId => {
                    if (!nodeIdByStep[parentId]) {
                        return;
                    }
                    graphLines.push(`${nodeIdByStep[parentId]} --> ${currentSafe}`);
                    ensureAdj(currentId).parents.add(parentId);
                    ensureAdj(parentId).children.add(currentId);
                });

                if (step.type === 'condition' && step.branches) {
                    const trueTarget = step.branches.true;
                    const falseTarget = step.branches.false;

                    if (trueTarget && nodeIdByStep[trueTarget]) {
                        graphLines.push(`${currentSafe} -- true --> ${nodeIdByStep[trueTarget]}`);
                        ensureAdj(currentId).children.add(trueTarget);
                        ensureAdj(trueTarget).parents.add(currentId);
                    }

                    if (falseTarget && nodeIdByStep[falseTarget]) {
                        graphLines.push(`${currentSafe} -- false --> ${nodeIdByStep[falseTarget]}`);
                        ensureAdj(currentId).children.add(falseTarget);
                        ensureAdj(falseTarget).parents.add(currentId);
                    }
                }
            });

            const renderDag = async () => {
                if (!window.mermaid) {
                    return false;
                }

                try {
                    mermaid.initialize({ startOnLoad: false, theme: 'dark', securityLevel: 'loose' });
                    const graph = graphLines.join('\n');
                    const { svg, bindFunctions } = await mermaid.render('workflowDagGraph', graph);
                    dagEl.innerHTML = svg;
                    if (bindFunctions) {
                        bindFunctions(dagEl);
                    }
                    return true;
                } catch (error) {
                    console.error('Mermaid render failed', error);
                    dagEl.innerHTML = '<div class="text-slate-500 text-xs">Failed to render DAG graph.</div>';
                    return false;
                }
            };

            const setupViewToggle = () => {
                if (!toggleEl) {
                    return;
                }

                toggleEl.addEventListener('click', (event) => {
                    const button = event.target.closest('.dag-toggle-btn');
                    if (!button) {
                        return;
                    }
                    const targetId = button.dataset.target;
                    if (!targetId) {
                        return;
                    }
                    const target = document.getElementById(targetId);
                    if (!target) {
                        return;
                    }
                    const isHidden = target.classList.toggle('hidden');
                    button.classList.toggle('dag-toggle-active', !isHidden);
                });
            };

            const wireInteractions = () => {
                const nodeElements = new Map();
                Object.entries(nodeIdByStep).forEach(([stepId, safeId]) => {
                    const nodeEl = dagEl.querySelector(`#flowchart-${safeId}`);
                    if (nodeEl) {
                        nodeEl.dataset.stepId = stepId;
                        nodeEl.classList.add('dag-node');
                        nodeElements.set(stepId, nodeEl);
                    }
                });

                const clearStates = () => {
                    nodeElements.forEach(nodeEl => {
                        nodeEl.classList.remove('dag-node--selected', 'dag-node--connected', 'dag-node--dim');
                    });
                };

                const applyStates = (selectedId) => {
                    if (!selectedId || !nodeElements.has(selectedId)) {
                        clearStates();
                        return;
                    }

                    const { parents, children } = adjacency[selectedId] || { parents: new Set(), children: new Set() };
                    nodeElements.forEach((nodeEl, stepId) => {
                        const isSelected = stepId === selectedId;
                        const isConnected = parents.has(stepId) || children.has(stepId);
                        nodeEl.classList.remove('dag-node--selected', 'dag-node--connected', 'dag-node--dim');
                        if (isSelected) {
                            nodeEl.classList.add('dag-node--selected');
                        } else if (isConnected) {
                            nodeEl.classList.add('dag-node--connected');
                        } else {
                            nodeEl.classList.add('dag-node--dim');
                        }
                    });
                };

                dagEl.addEventListener('click', (event) => {
                    const targetNode = event.target.closest('.dag-node');
                    if (!targetNode) {
                        applyStates(null);
                        return;
                    }
                    applyStates(targetNode.dataset.stepId || null);
                });
            };

            renderDag().then((rendered) => {
                if (rendered) {
                    wireInteractions();
                } else if (linearEl) {
                    linearEl.classList.remove('hidden');
                }
            });

            setupViewToggle();
        })();
    </script>
@endpush
