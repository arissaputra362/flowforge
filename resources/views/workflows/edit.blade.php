@extends('layouts.app')

@section('title', 'Edit Workflow')
@section('page_title', 'Edit Workflow')
@section('page_subtitle', 'Update workflow metadata and versioned definition.')

@section('header_actions')
    <a href="/workflows/{{ $workflow->id }}"
        class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 text-sm font-medium transition">
        Back to Detail
    </a>
@endsection

@section('content')
    @php
        $latestVersion = $workflow->latestVersion;
        $definition = $latestVersion->definition ?? ['steps' => []];
        $steps = $definition['steps'] ?? [];
        $currentTrigger = $workflow->triggers->first();
        $currentTriggerType = old('trigger_type', $currentTrigger->type ?? 'manual');
        $currentCronExpression = old('cron_expression', data_get($currentTrigger, 'config.cron_expression'));
    @endphp

    <div class="max-w-6xl mx-auto fade-in">
        <form id="workflowForm" class="glass p-8 space-y-8">
            @csrf

            {{-- ================= BASIC INFO ================= --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Workflow Name</label>
                    <input type="text" name="name" value="{{ old('name', $workflow->name) }}"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white"
                        placeholder="e.g. Send Welcome Email" required>
                    @error('name')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Trigger Type</label>
                    <select name="trigger_type" id="trigger_type"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white">
                        <option value="manual" @selected($currentTriggerType === 'manual')>Manual</option>
                        <option value="cron" @selected($currentTriggerType === 'cron')>Scheduled (Cron)</option>
                        <option value="webhook" @selected($currentTriggerType === 'webhook')>Webhook</option>
                    </select>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Description</label>
                <textarea name="description" rows="3" id="description"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white"
                    placeholder="Short description">{{ old('description', $workflow->description) }}</textarea>
            </div>

            {{-- Cron --}}
            <div id="cron-field" class="hidden">
                <label class="block text-sm font-medium text-slate-300 mb-2">Cron Expression</label>
                <input type="text" name="cron_expression" value="{{ $currentCronExpression }}"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white"
                    placeholder="* * * * *">
            </div>

            {{-- ================= BUILDER: 2 KOLOM ================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="border border-white/10 rounded-xl p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-white font-semibold">Steps Builder</h3>
                            <p class="text-xs text-slate-400">Edit dan konfigurasi steps</p>
                        </div>
                    </div>
                    <div id="steps-container" class="space-y-3"></div>
                </div>

                <div class="border border-white/10 rounded-xl p-6">
                    <div class="mb-4">
                        <h3 class="text-white font-semibold">Flow Preview</h3>
                        <p class="text-xs text-slate-400">Visual execution order</p>
                    </div>
                    <div id="flow-preview" class="flex flex-col items-center gap-0 min-h-32">
                        <p class="text-slate-600 text-xs text-center mt-8">Add steps to see preview</p>
                    </div>
                </div>
            </div>

            <input type="hidden" name="definition" id="definition">

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4">
                <a href="/workflows/{{ $workflow->id }}"
                    class="px-5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10">
                    Cancel
                </a>
                <button type="button" onclick="submitWorkflow()"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">
                    Update Workflow
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const WORKFLOW_ID = "{{ $workflow->id }}";
        const TOKEN = "{{ session('api_token') }}";
        const triggerSelect = document.getElementById('trigger_type');
        const cronField = document.getElementById('cron-field');

        function toggleCron() {
            cronField.classList.toggle('hidden', triggerSelect.value !== 'cron');
        }
        triggerSelect.addEventListener('change', toggleCron);
        toggleCron();

        let steps = @json($steps);

        function normalizeSteps() {
            steps = Array.isArray(steps) ? steps : [];
            steps = steps.map((step, index) => ({
                id: step.id || `step_${index + 1}`,
                type: step.type || 'http',
                depends_on: Array.isArray(step.depends_on) ? step.depends_on : [],
                config: step.config || {},
                branches: {
                    true: step.branches?.true ?? null,
                    false: step.branches?.false ?? null,
                }
            }));
        }

        normalizeSteps();

        function addStep() {
            const index = steps.length;
            const prevStep = index > 0 ? steps[index - 1] : null;
            const prevIsCondition = prevStep?.type === 'condition';
            const prevIsBranchTarget = prevStep ? steps.some(s =>
                s.type === 'condition' &&
                (s.branches?.true === prevStep.id || s.branches?.false === prevStep.id)
            ) : false;

            const dependsOn = (index === 0 || prevIsCondition || prevIsBranchTarget) ? [] : [steps[index - 1].id];

            steps.push({
                id: 'step_' + (index + 1),
                type: 'http',
                depends_on: dependsOn,
                config: {},
                branches: { true: null, false: null }
            });

            renderSteps();
            renderPreview();
            syncJSON();
        }

        function removeStep(index) {
            const removedId = steps[index].id;
            steps.splice(index, 1);

            steps.forEach(s => {
                s.depends_on = s.depends_on.filter(d => d !== removedId);
                if (s.branches?.true === removedId) s.branches.true = null;
                if (s.branches?.false === removedId) s.branches.false = null;
            });

            renderSteps();
            renderPreview();
            syncJSON();
        }

        function updateType(index, value) {
            const currentDependsOn = steps[index].depends_on;
            steps[index].type = value;
            steps[index].config = {};
            steps[index].depends_on = currentDependsOn;
            steps[index].branches = { true: null, false: null };

            renderSteps();
            renderPreview();
            syncJSON();
        }

        function updateConfig(index, key, value) {
            steps[index].config[key] = value;
            syncJSON();
        }

        function updateBranch(index, key, value) {
            if (!steps[index].branches) {
                steps[index].branches = { true: null, false: null };
            }

            steps[index].branches[key] = value || null;

            if (value) {
                const targetStep = steps.find(s => s.id === value);
                if (targetStep) {
                    targetStep.depends_on = [];
                }
            }

            renderSteps();
            renderPreview();
            syncJSON();
        }

        function renderSteps() {
            const container = document.getElementById('steps-container');
            container.innerHTML = '';

            steps.forEach((step, index) => {
                const isBranchTarget = steps.some(s =>
                    s.type === 'condition' &&
                    (s.branches?.true === step.id || s.branches?.false === step.id)
                );

                const typeColors = {
                    http: 'border-blue-500/40',
                    delay: 'border-yellow-500/40',
                    condition: 'border-purple-500/60',
                    script: 'border-green-500/40',
                };

                const borderClass = typeColors[step.type] || 'border-white/10';

                container.innerHTML += `
                <div class="bg-white/5 border ${borderClass} rounded-lg p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-white font-semibold text-sm">Step ${index + 1}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-white/10 text-slate-400">${step.id}</span>
                            ${isBranchTarget ? `<span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300">branch target</span>` : ''}
                        </div>
                        <button type="button" onclick="removeStep(${index})"
                            class="text-red-400 text-xs hover:text-red-300">Remove</button>
                    </div>

                    <div>
                        <label class="text-xs text-slate-400">Type</label>
                        <select onchange="updateType(${index}, this.value)"
                            class="w-full bg-black/40 text-white p-2 rounded text-sm">
                            <option value="http" ${step.type === 'http' ? 'selected' : ''}>HTTP Call</option>
                            <option value="script" ${step.type === 'script' ? 'selected' : ''}>Script Execution</option>
                            <option value="delay" ${step.type === 'delay' ? 'selected' : ''}>Delay / Wait</option>
                            <option value="condition" ${step.type === 'condition' ? 'selected' : ''}>Conditional Branch</option>
                        </select>
                    </div>

                    ${renderConfig(step, index)}

                    <p class="text-xs text-slate-600">
                        Depends on: ${step.depends_on.length ? step.depends_on.join(', ') : 'none'}
                    </p>
                </div>
            `;
            });

            container.innerHTML += `
                <button type="button" onclick="addStep()"
                    class="w-full mt-2 px-4 py-3 rounded-xl border border-dashed border-indigo-500/40 bg-indigo-500/10 text-indigo-300 hover:bg-indigo-500/15 text-sm font-medium transition">
                    + Add Step
                </button>
            `;
        }

        function renderConfig(step, index) {
            if (step.type === 'http') {
                if (!step.config.method) {
                    step.config.method = 'GET';
                    syncJSON();
                }
                return `
                <div>
                    <label class="text-xs text-slate-400">URL</label>
                    <input type="text"
                        value="${step.config.url || ''}"
                        oninput="updateConfig(${index}, 'url', this.value)"
                        placeholder="https://example.com/api"
                        class="w-full bg-black/40 text-white p-2 rounded text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-400">Method</label>
                    <select onchange="updateConfig(${index}, 'method', this.value)"
                        class="w-full bg-black/40 text-white p-2 rounded text-sm">
                        <option value="GET" ${step.config.method === 'GET' ? 'selected' : ''}>GET</option>
                        <option value="POST" ${step.config.method === 'POST' ? 'selected' : ''}>POST</option>
                        <option value="PUT" ${step.config.method === 'PUT' ? 'selected' : ''}>PUT</option>
                    </select>
                </div>
            `;
            }

            if (step.type === 'script') {
                return `
                <div>
                    <label class="text-xs text-slate-400">Script (JS)</label>
                    <textarea
                        oninput="updateConfig(${index}, 'code', this.value)"
                        class="w-full bg-black/40 text-green-400 p-2 rounded font-mono text-xs"
                        rows="4"
                        placeholder="console.log('hello');">${step.config.code || ''}</textarea>
                </div>
            `;
            }

            if (step.type === 'delay') {
                return `
                <div>
                    <label class="text-xs text-slate-400">Duration (ms)</label>
                    <input type="number"
                        value="${step.config.duration || ''}"
                        oninput="updateConfig(${index}, 'duration', this.value)"
                        class="w-full bg-black/40 text-white p-2 rounded text-sm">
                </div>
            `;
            }

            if (step.type === 'condition') {
                const otherSteps = steps.filter(s => s.id !== step.id);

                const trueOptions = otherSteps.map(s =>
                    `<option value="${s.id}" ${step.branches?.true === s.id ? 'selected' : ''}>${s.id} (${s.type})</option>`
                ).join('');

                const falseOptions = otherSteps.map(s =>
                    `<option value="${s.id}" ${step.branches?.false === s.id ? 'selected' : ''}>${s.id} (${s.type})</option>`
                ).join('');

                return `
                <div>
                    <label class="text-xs text-slate-400">Expression</label>
                    <input type="text"
                        value="${step.config.expression || ''}"
                        oninput="updateConfig(${index}, 'expression', this.value)"
                        class="w-full bg-black/40 text-white p-2 rounded text-sm"
                        placeholder="step.step_1.output.status == 200">
                    <p class="text-xs text-slate-600 mt-1">Format: step.&lt;step_id&gt;.output.&lt;field&gt; == value</p>
                </div>

                <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-3 text-xs text-purple-300">
                    Tambahkan semua branch step terlebih dahulu, lalu pilih di bawah.
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-green-400">✓ True → Step</label>
                        <select onchange="updateBranch(${index}, 'true', this.value)"
                            class="w-full bg-black/40 text-white p-2 rounded text-sm mt-1">
                            <option value="">— pilih step —</option>
                            ${trueOptions}
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-red-400">✕ False → Step</label>
                        <select onchange="updateBranch(${index}, 'false', this.value)"
                            class="w-full bg-black/40 text-white p-2 rounded text-sm mt-1">
                            <option value="">— pilih step —</option>
                            ${falseOptions}
                        </select>
                    </div>
                </div>
            `;
            }

            return '';
        }

        function renderPreview() {
            const container = document.getElementById('flow-preview');
            container.innerHTML = '';

            if (steps.length === 0) {
                container.innerHTML = '<p class="text-slate-600 text-xs text-center mt-8">Add steps to see preview</p>';
                return;
            }

            const typeIcon = {
                http: '🌐',
                delay: '⏱',
                condition: '◆',
                script: '⚡'
            };
            const typeColor = {
                http: 'border-blue-500/40 text-blue-400',
                delay: 'border-yellow-500/40 text-yellow-400',
                condition: 'border-purple-500/60 text-purple-400',
                script: 'border-green-500/40 text-green-400',
            };

            const branchTargets = {};
            steps.forEach(s => {
                if (s.type === 'condition') {
                    if (s.branches?.true) branchTargets[s.branches.true] = { from: s.id, side: 'true' };
                    if (s.branches?.false) branchTargets[s.branches.false] = { from: s.id, side: 'false' };
                }
            });

            const renderNode = (step) => {
                const colors = typeColor[step.type] || 'border-white/20 text-slate-400';
                const icon = typeIcon[step.type] || '●';
                return `
                <div class="flex flex-col items-center">
                    <div class="border ${colors} bg-black/30 rounded-lg px-3 py-2 text-center min-w-30">
                        <div class="text-base">${icon}</div>
                        <div class="text-xs font-semibold text-white mt-1">${step.id}</div>
                        <div class="text-xs text-slate-500">${step.type}</div>
                        ${step.type === 'condition' && step.config.expression
                            ? `<div class="text-xs text-purple-400 mt-1 font-mono truncate max-w-30">${step.config.expression}</div>`
                            : ''}
                    </div>
                </div>
            `;
            };

            const arrowDown = `
            <div class="flex justify-center">
                <div style="width:2px;height:20px;background:rgba(255,255,255,0.15);position:relative;">
                    <div style="position:absolute;bottom:-4px;left:-3px;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid rgba(255,255,255,0.2);"></div>
                </div>
            </div>
        `;

            const renderBranch = (condStep) => {
                const trueStep = steps.find(s => s.id === condStep.branches?.true);
                const falseStep = steps.find(s => s.id === condStep.branches?.false);

                if (!trueStep && !falseStep) return '';

                return `
                <div class="w-full mt-3">
                    <svg width="100%" height="40" viewBox="0 0 220 40" preserveAspectRatio="xMidYMid meet">
                        <line x1="110" y1="0" x2="55"  y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3"/>
                        <line x1="110" y1="0" x2="165" y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3"/>
                        <rect x="20"  y="12" width="32" height="14" rx="7" fill="rgba(34,197,94,0.15)"/>
                        <text x="36"  y="23" text-anchor="middle" font-size="9" fill="#4ade80" font-family="sans-serif">true</text>
                        <rect x="168" y="12" width="34" height="14" rx="7" fill="rgba(239,68,68,0.15)"/>
                        <text x="185" y="23" text-anchor="middle" font-size="9" fill="#f87171" font-family="sans-serif">false</text>
                    </svg>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col items-center">
                            ${trueStep ? renderNode(trueStep) : '<div class="text-xs text-slate-600 text-center">—</div>'}
                        </div>
                        <div class="flex flex-col items-center">
                            ${falseStep ? renderNode(falseStep) : '<div class="text-xs text-slate-600 text-center">—</div>'}
                        </div>
                    </div>
                </div>
            `;
            };

            const rendered = [];
            steps.forEach((step) => {
                if (branchTargets[step.id]) return;

                if (rendered.length > 0) {
                    rendered.push(arrowDown);
                }

                rendered.push(renderNode(step));

                if (step.type === 'condition') {
                    rendered.push(renderBranch(step));
                }
            });

            container.innerHTML = rendered.join('');
        }

        function syncJSON() {
            document.getElementById('definition').value = JSON.stringify({ steps }, null, 2);
        }

        async function submitWorkflow() {
            syncJSON();

            const payload = {
                name: document.querySelector('[name="name"]').value,
                description: document.getElementById('description').value,
                trigger_type: document.querySelector('[name="trigger_type"]').value,
                cron_expression: document.querySelector('[name="cron_expression"]')?.value || null,
                definition: document.getElementById('definition').value
            };

            try {
                const response = await fetch(`/api/workflows/${WORKFLOW_ID}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + TOKEN
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (!response.ok) throw data;

                alert('Workflow updated successfully!');
                window.location.href = `/workflows/${WORKFLOW_ID}`;
            } catch (error) {
                console.error(error);
                alert(error.message || 'Failed to update workflow');
            }
        }

        renderSteps();
        renderPreview();
        syncJSON();
    </script>
@endpush
