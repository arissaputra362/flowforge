@extends('layouts.app')

@section('title', 'Create Workflow')
@section('page_title', 'Create Workflow')
@section('page_subtitle', 'Define a new workflow orchestration.')

@section('content')
    <div class="max-w-6xl mx-auto fade-in">

        <form id="workflowForm" class="glass p-8 space-y-8">
            @csrf

            {{-- ================= BASIC INFO ================= --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Workflow Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white"
                        placeholder="e.g. Send Welcome Email" required>
                    @error('name')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Trigger Type</label>
                    <select name="trigger_type" id="trigger_type"
                        class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-3 text-white
           [&>option]:bg-slate-800 [&>option]:text-white">
                        <option value="manual">Manual</option>
                        <option value="cron">Scheduled (Cron)</option>
                        <option value="webhook">Webhook</option>
                    </select>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Description</label>
                <textarea name="description" rows="3" id="description"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" placeholder="Short description">{{ old('description') }}</textarea>
            </div>

            {{-- Cron --}}
            <div id="cron-field" class="hidden">
                <label class="block text-sm font-medium text-slate-300 mb-2">Cron Expression</label>
                <input type="text" name="cron_expression"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white"
                    placeholder="* * * * *">
            </div>

            {{-- ================= BUILDER: 2 KOLOM ================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Kiri: Form Steps --}}
                <div class="border border-white/10 rounded-xl p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-white font-semibold">Steps Builder</h3>
                            <p class="text-xs text-slate-400">Tambah dan konfigurasi steps</p>
                        </div>
                    </div>
                    <div id="steps-container" class="space-y-3"></div>
                </div>

                {{-- Kanan: Visual Preview --}}
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
                <a href="/workflows"
                    class="px-5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10">
                    Cancel
                </a>
                <button type="button" onclick="submitWorkflow()"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">
                    Save Workflow
                </button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const TOKEN = "{{ session('api_token') }}";
        const triggerSelect = document.getElementById('trigger_type');
        const cronField = document.getElementById('cron-field');

        function toggleCron() {
            cronField.classList.toggle('hidden', triggerSelect.value !== 'cron');
        }
        triggerSelect.addEventListener('change', toggleCron);
        toggleCron();

        // ========================= STATE =========================
        let steps = [];

        // ========================= ADD STEP =========================
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
                branches: {
                    true: null,
                    false: null
                }
            });

            renderSteps();
            renderPreview();
            syncJSON();
        }

        // ========================= REMOVE STEP =========================
        function removeStep(index) {
            const removedId = steps[index].id;
            steps.splice(index, 1);

            // Bersihkan referensi ke step yang dihapus
            steps.forEach(s => {
                s.depends_on = s.depends_on.filter(d => d !== removedId);
                if (s.branches?.true === removedId) s.branches.true = null;
                if (s.branches?.false === removedId) s.branches.false = null;
            });

            renderSteps();
            renderPreview();
            syncJSON();
        }

        // ========================= UPDATE TYPE =========================
        function updateType(index, value) {
            const currentDependsOn = steps[index].depends_on;
            steps[index].type = value;
            steps[index].config = {};
            steps[index].depends_on = currentDependsOn;
            steps[index].branches = {
                true: null,
                false: null
            };

            renderSteps();
            renderPreview();
            syncJSON();
        }

        // ========================= UPDATE CONFIG =========================
        function updateConfig(index, key, value) {
            steps[index].config[key] = value;
            syncJSON();
        }

        // ========================= UPDATE BRANCH =========================
        function updateBranch(index, key, value) {
            if (!steps[index].branches) {
                steps[index].branches = {
                    true: null,
                    false: null
                };
            }

            steps[index].branches[key] = value || null;

            // Auto-reset depends_on branch target ke kosong
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

        function updateDependsOn(index, selectedIds) {
            steps[index].depends_on = selectedIds;
            renderPreview();
            syncJSON();
        }

        // ========================= RENDER DEPENDS ON =========================
        function renderDependsOn(step, index) {
            const otherSteps = steps.filter(s => s.id !== step.id);
            if (otherSteps.length === 0) {
                return '<p class="text-xs text-slate-600">Depends on: none (first step)</p>';
            }
            const checkboxes = otherSteps.map(s => {
                const checked = step.depends_on.includes(s.id) ? 'checked' : '';
                return `<label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                    <input type="checkbox" value="${s.id}" ${checked}
                        onchange="handleDependsOnChange(${index}, this)"
                        class="accent-indigo-400">
                    <span class="px-1.5 py-0.5 rounded bg-white/10 font-mono">${s.id}</span>
                    <span class="text-slate-500">(${s.type})</span>
                </label>`;
            }).join('');
            return `
                <div>
                    <label class="text-xs text-slate-400 block mb-1">Depends on</label>
                    <div class="flex flex-wrap gap-2 bg-black/20 rounded-lg p-2">
                        ${checkboxes}
                    </div>
                </div>
            `;
        }

        function handleDependsOnChange(index, checkbox) {
            // Kumpulkan semua checkbox yang checked untuk step ini
            const container = checkbox.closest('.flex.flex-wrap');
            const selected = [...container.querySelectorAll('input[type=checkbox]:checked')]
                .map(el => el.value);
            updateDependsOn(index, selected);
        }

        // ========================= RENDER STEPS (FORM) =========================
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
                            <option value="http"      ${step.type === 'http'      ? 'selected' : ''}>HTTP Call</option>
                            <option value="script"    ${step.type === 'script'    ? 'selected' : ''}>Script Execution</option>
                            <option value="delay"     ${step.type === 'delay'     ? 'selected' : ''}>Delay / Wait</option>
                            <option value="condition" ${step.type === 'condition' ? 'selected' : ''}>Conditional Branch</option>
                        </select>
                    </div>

                    ${renderConfig(step, index)}

                    ${renderDependsOn(step, index)}
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

        // ========================= RENDER CONFIG =========================
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
                        <option value="GET"  ${step.config.method === 'GET'  ? 'selected' : ''}>GET</option>
                        <option value="POST" ${step.config.method === 'POST' ? 'selected' : ''}>POST</option>
                        <option value="PUT"  ${step.config.method === 'PUT'  ? 'selected' : ''}>PUT</option>
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

        // ========================= RENDER PREVIEW =========================
        // function renderPreview() {
        //     const container = document.getElementById('flow-preview');
        //     container.innerHTML = '';

        //     if (steps.length === 0) {
        //         container.innerHTML = '<p class="text-slate-600 text-xs text-center mt-8">Add steps to see preview</p>';
        //         return;
        //     }

        //     const typeIcon = {
        //         http: '🌐',
        //         delay: '⏱',
        //         condition: '◆',
        //         script: '⚡'
        //     };
        //     const typeColor = {
        //         http: 'border-blue-500/40 text-blue-400',
        //         delay: 'border-yellow-500/40 text-yellow-400',
        //         condition: 'border-purple-500/60 text-purple-400',
        //         script: 'border-green-500/40 text-green-400',
        //     };

        //     // Tentukan step mana yang branch target
        //     const branchTargets = {};
        //     steps.forEach(s => {
        //         if (s.type === 'condition') {
        //             if (s.branches?.true) branchTargets[s.branches.true] = {
        //                 from: s.id,
        //                 side: 'true'
        //             };
        //             if (s.branches?.false) branchTargets[s.branches.false] = {
        //                 from: s.id,
        //                 side: 'false'
        //             };
        //         }
        //     });

        //     // Render node
        //     const renderNode = (step) => {
        //         const colors = typeColor[step.type] || 'border-white/20 text-slate-400';
        //         const icon = typeIcon[step.type] || '●';
        //         return `
    //         <div class="flex flex-col items-center">
    //             <div class="border ${colors} bg-black/30 rounded-lg px-3 py-2 text-center min-w-[100px]">
    //                 <div class="text-base">${icon}</div>
    //                 <div class="text-xs font-semibold text-white mt-1">${step.id}</div>
    //                 <div class="text-xs text-slate-500">${step.type}</div>
    //                 ${step.type === 'condition' && step.config.expression
    //                     ? `<div class="text-xs text-purple-400 mt-1 font-mono truncate max-w-[120px]">${step.config.expression}</div>`
    //                     : ''}
    //             </div>
    //         </div>
    //     `;
        //     };

        //     // Render arrow
        //     const arrowDown = `
    //     <div class="flex justify-center">
    //         <div style="width:2px;height:20px;background:rgba(255,255,255,0.15);position:relative;">
    //             <div style="position:absolute;bottom:-4px;left:-3px;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid rgba(255,255,255,0.2);"></div>
    //         </div>
    //     </div>
    //     `;

        //     // Render branch split untuk condition
        //     const renderBranch = (condStep) => {
        //         const trueStep = steps.find(s => s.id === condStep.branches?.true);
        //         const falseStep = steps.find(s => s.id === condStep.branches?.false);

        //         if (!trueStep && !falseStep) return '';

        //         return `
    //         <div class="w-full">
    //             <svg width="100%" height="40" viewBox="0 0 220 40" preserveAspectRatio="xMidYMid meet">
    //                 <line x1="110" y1="0" x2="55"  y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3"/>
    //                 <line x1="110" y1="0" x2="165" y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3"/>
    //                 <rect x="20"  y="12" width="32" height="14" rx="7" fill="rgba(34,197,94,0.15)"/>
    //                 <text x="36"  y="23" text-anchor="middle" font-size="9" fill="#4ade80" font-family="sans-serif">true</text>
    //                 <rect x="168" y="12" width="34" height="14" rx="7" fill="rgba(239,68,68,0.15)"/>
    //                 <text x="185" y="23" text-anchor="middle" font-size="9" fill="#f87171" font-family="sans-serif">false</text>
    //             </svg>
    //             <div class="grid grid-cols-2 gap-3">
    //                 <div class="flex flex-col items-center">
    //                     ${trueStep ? renderNode(trueStep) : '<div class="text-xs text-slate-600 text-center">—</div>'}
    //                 </div>
    //                 <div class="flex flex-col items-center">
    //                     ${falseStep ? renderNode(falseStep) : '<div class="text-xs text-slate-600 text-center">—</div>'}
    //                 </div>
    //             </div>
    //         </div>
    //     `;
        //     };

        //     // Loop render — skip branch targets (sudah dirender di dalam branch section)
        //     const rendered = [];
        //     steps.forEach((step, i) => {
        //         if (branchTargets[step.id]) return; // skip, akan dirender di branch section

        //         // Arrow sebelum node (kecuali pertama)
        //         if (rendered.length > 0) {
        //             rendered.push(arrowDown);
        //         }

        //         rendered.push(renderNode(step));

        //         // Kalau condition dan punya branch, render branch section
        //         if (step.type === 'condition') {
        //             rendered.push(renderBranch(step));
        //         }
        //     });

        //     container.innerHTML = rendered.join('');
        // }


        // ========================= HELPER: BRANCH SUBTREE =========================
        // Kembalikan Set berisi semua step ID yang berada di subtree sisi branch tertentu
        // dari sebuah condition step
        function getBranchSubtree(conditionStepId, side) {
            const condStep = steps.find(s => s.id === conditionStepId);
            if (!condStep) return new Set();

            const rootId = condStep.branches?.[side];
            if (!rootId) return new Set();

            const result = new Set();
            const visited = new Set();
            const queue = [rootId];

            while (queue.length) {
                const id = queue.shift();
                if (visited.has(id)) continue;
                visited.add(id);
                result.add(id);

                // Tambahkan semua step yang depends_on step ini
                steps.forEach(s => {
                    if (s.depends_on.includes(id) && !visited.has(s.id)) {
                        queue.push(s.id);
                    }
                });
            }

            return result;
        }

        // ========================= RENDER PREVIEW =========================
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

            // ---- bangun adjacency: semua edges (depends_on + branches) ----
            // parentMap[id] = array of parent IDs
            const parentMap = {};
            steps.forEach(s => {
                parentMap[s.id] = [];
            });

            steps.forEach(s => {
                // depends_on edges
                s.depends_on.forEach(dep => {
                    if (parentMap[s.id]) parentMap[s.id].push(dep);
                });
                // branch edges
                if (s.type === 'condition') {
                    if (s.branches?.true && parentMap[s.branches.true]) parentMap[s.branches.true].push(s.id);
                    if (s.branches?.false && parentMap[s.branches.false]) parentMap[s.branches.false].push(s.id);
                }
            });

            // ---- hitung rank = max(rank semua parent) + 1, iterasi hingga stabil ----
            const rank = {};
            steps.forEach(s => rank[s.id] = 0);

            let changed = true;
            let safetyLimit = 0;
            while (changed && safetyLimit++ < 100) {
                changed = false;
                steps.forEach(s => {
                    parentMap[s.id].forEach(pid => {
                        if (rank[pid] !== undefined && rank[s.id] <= rank[pid]) {
                            rank[s.id] = rank[pid] + 1;
                            changed = true;
                        }
                    });
                });
            }

            // ---- kelompokkan per rank ----
            const levels = {};
            steps.forEach(s => {
                const r = rank[s.id];
                if (!levels[r]) levels[r] = [];
                levels[r].push(s);
            });
            const sortedLevels = Object.keys(levels).map(Number).sort((a, b) => a - b);

            // ---- cek apakah transisi antar level adalah branch split ----
            // branch split = level sebelumnya punya condition node yang branch ke level ini
            function isBranchSplit(prevLevelNum, curLevelNum) {
                const prevSteps = levels[prevLevelNum] || [];
                const curSteps = levels[curLevelNum] || [];
                return prevSteps.some(ps =>
                    ps.type === 'condition' &&
                    curSteps.some(cs => ps.branches?.true === cs.id || ps.branches?.false === cs.id)
                );
            }

            // ---- render helpers ----
            const renderNode = (step) => {
                const colors = typeColor[step.type] || 'border-white/20 text-slate-400';
                const icon = typeIcon[step.type] || '●';
                const depList = step.depends_on.length ?
                    `<div class="text-xs text-slate-600 mt-1 font-mono truncate">← ${step.depends_on.join(', ')}</div>` :
                    '';
                return `
                    <div class="border ${colors} bg-black/30 rounded-lg px-3 py-2 text-center min-w-[90px] max-w-[130px]">
                        <div class="text-base">${icon}</div>
                        <div class="text-xs font-semibold text-white mt-1">${step.id}</div>
                        <div class="text-xs text-slate-500">${step.type}</div>
                        ${step.type === 'condition' && step.config.expression
                            ? `<div class="text-xs text-purple-400 mt-1 font-mono truncate max-w-[110px]">${step.config.expression}</div>`
                            : ''}
                        ${depList}
                    </div>`;
            };

            const arrowDown = `
                <div class="flex justify-center w-full">
                    <div style="width:2px;height:18px;background:rgba(255,255,255,0.15);position:relative;">
                        <div style="position:absolute;bottom:-4px;left:-3px;
                            border-left:4px solid transparent;border-right:4px solid transparent;
                            border-top:5px solid rgba(255,255,255,0.2);"></div>
                    </div>
                </div>`;

            const branchSplitSVG = `
                <div class="w-full">
                    <svg width="100%" height="40" viewBox="0 0 220 40" preserveAspectRatio="xMidYMid meet">
                        <line x1="110" y1="0" x2="55"  y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3"/>
                        <line x1="110" y1="0" x2="165" y2="38" stroke="#a78bfa" stroke-width="1.5" stroke-dasharray="4,3"/>
                        <rect x="20"  y="12" width="32" height="14" rx="7" fill="rgba(34,197,94,0.15)"/>
                        <text x="36"  y="23" text-anchor="middle" font-size="9" fill="#4ade80" font-family="sans-serif">true</text>
                        <rect x="168" y="12" width="34" height="14" rx="7" fill="rgba(239,68,68,0.15)"/>
                        <text x="185" y="23" text-anchor="middle" font-size="9" fill="#f87171" font-family="sans-serif">false</text>
                    </svg>
                </div>`;

            // ---- susun HTML ----
            // Ganti seluruh bagian "susun HTML" dengan ini:
            const html = [];
            sortedLevels.forEach((levelNum, li) => {
                const stepsInLevel = levels[levelNum];
                const prevLevelNum = li > 0 ? sortedLevels[li - 1] : null;

                if (li > 0) {
                    if (isBranchSplit(prevLevelNum, levelNum) && stepsInLevel.length >= 2) {
                        html.push(branchSplitSVG);
                    } else {
                        html.push(arrowDown);
                    }
                }

                if (stepsInLevel.length === 1) {
                    // Cek apakah step ini adalah child dari salah satu sisi branch
                    // Jika ya, render dalam grid 2 kolom dengan placeholder di sisi lain
                    const prevSteps = prevLevelNum !== null ? (levels[prevLevelNum] || []) : [];
                    const condInPrev = prevSteps.find(ps => ps.type === 'condition');

                    if (condInPrev) {
                        const trueTarget = condInPrev.branches?.true;
                        const falseTarget = condInPrev.branches?.false;

                        // Cari step di level sebelumnya yang jadi parent langsung
                        // via depends_on ke step ini
                        const isChildOfTrue = stepsInLevel[0].depends_on.includes(trueTarget) ||
                            stepsInLevel[0].id === trueTarget;
                        const isChildOfFalse = stepsInLevel[0].depends_on.includes(falseTarget) ||
                            stepsInLevel[0].id === falseTarget;

                        // Lebih akurat: cek apakah parent langsung step ini ada di branch true/false side
                        const trueSubtree = getBranchSubtree(condInPrev.id, 'true');
                        const falseSubtree = getBranchSubtree(condInPrev.id, 'false');
                        const stepId = stepsInLevel[0].id;

                        if (trueSubtree.has(stepId) && !falseSubtree.has(stepId)) {
                            // step ini di sisi true, kolom kanan kosong
                            html.push(`
                                <div class="grid grid-cols-2 gap-3 w-full">
                                    <div class="flex justify-center">${renderNode(stepsInLevel[0])}</div>
                                    <div></div>
                                </div>`);
                            return;
                        } else if (falseSubtree.has(stepId) && !trueSubtree.has(stepId)) {
                            // step ini di sisi false, kolom kiri kosong
                            html.push(`
                                <div class="grid grid-cols-2 gap-3 w-full">
                                    <div></div>
                                    <div class="flex justify-center">${renderNode(stepsInLevel[0])}</div>
                                </div>`);
                            return;
                        }
                    }

                    html.push(`<div class="flex justify-center w-full">${renderNode(stepsInLevel[0])}</div>`);
                } else {
                    const cols = stepsInLevel.length;
                    const nodes = stepsInLevel.map(s =>
                        `<div class="flex justify-center">${renderNode(s)}</div>`
                    ).join('');
                    html.push(`<div class="grid grid-cols-${cols} gap-3 w-full">${nodes}</div>`);
                }
            });

            container.innerHTML = html.join('');
        }

        // ========================= SYNC JSON =========================
        function syncJSON() {
            document.getElementById('definition').value = JSON.stringify({
                steps
            }, null, 2);
        }

        // ========================= SUBMIT =========================
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
                const response = await fetch("{{ route('workflows.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "Authorization": "Bearer " + TOKEN
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (!response.ok) throw data;

                alert("Workflow created successfully!");
                window.location.href = "/workflows";

            } catch (error) {
                console.error(error);
                alert(error.message || "Failed to create workflow");
            }
        }

        renderSteps();
        renderPreview();
    </script>
@endpush
