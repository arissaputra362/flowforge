@extends('layouts.app')

@section('title', 'Create Workflow')
@section('page_title', 'Create Workflow')
@section('page_subtitle', 'Define a new workflow orchestration.')

@section('content')
    <div class="max-w-5xl mx-auto fade-in">

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
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white">
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

            {{-- ================= SEMI BUILDER ================= --}}
            <div class="border border-white/10 rounded-xl p-6 space-y-4">

                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-white font-semibold">Workflow Steps Builder</h3>
                        <p class="text-xs text-slate-400">Define execution steps visually (no JSON needed)</p>
                    </div>

                    <button type="button" onclick="addStep()"
                        class="px-4 py-2 bg-indigo-600 rounded-lg text-white text-sm">
                        + Add Step
                    </button>
                </div>

                <div id="steps-container" class="space-y-4"></div>

            </div>

            {{-- hidden DAG output --}}
            <input type="hidden" name="definition" id="definition">

            {{-- ================= ACTIONS ================= --}}
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


        // =========================
        // WORKFLOW BUILDER STATE
        // =========================
        let steps = [];

        // Add step
        function addStep() {
            steps.push({
                id: 'step_' + (steps.length + 1),
                type: 'http',
                depends_on: [],
                config: {}
            });

            renderSteps();
            syncJSON();
        }

        // Remove step
        function removeStep(index) {
            steps.splice(index, 1);
            renderSteps();
            syncJSON();
        }

        // Update type
        function updateType(index, value) {
            steps[index].type = value;
            steps[index].config = {};
            steps[index].branches = {
                true: null,
                false: null
            };

            renderSteps();
            syncJSON();
        }

        // Update config
        function updateConfig(index, key, value) {
            steps[index].config[key] = value;
            syncJSON();
        }

        // Render steps
        function renderSteps() {
            const container = document.getElementById('steps-container');
            container.innerHTML = '';

            steps.forEach((step, index) => {

                container.innerHTML += `
        <div class="bg-white/5 border border-white/10 rounded-lg p-4 space-y-3">

            <div class="flex justify-between">
                <div class="text-white font-semibold">Step ${index + 1}</div>
                <button type="button" onclick="removeStep(${index})"
                    class="text-red-400 text-sm">Remove</button>
            </div>

            <div>
                <label class="text-xs text-slate-400">Type</label>
               <select onchange="updateType(${index}, this.value)"
                    class="w-full bg-black/40 text-white p-2 rounded">

                    <option value="http" ${step.type === 'http' ? 'selected' : ''}>HTTP Call</option>
                    <option value="script" ${step.type === 'script' ? 'selected' : ''}>Script Execution</option>
                    <option value="delay" ${step.type === 'delay' ? 'selected' : ''}>Delay / Wait</option>
                    <option value="condition" ${step.type === 'condition' ? 'selected' : ''}>Conditional Branch</option>

                </select>
            </div>

            ${renderConfig(step, index)}

        </div>
        `;
            });
        }

        // Render config per type
        function renderConfig(step, index) {

            if (step.type === 'http') {
                // Set default method saat pertama kali jika belum ada
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
                            placeholder="https://example.com/api/endpoint"
                            class="w-full bg-black/40 text-white p-2 rounded">
                        <p class="text-xs text-slate-600 mt-1">Include https:// prefix</p>
                    </div>

                    <div>
                        <label class="text-xs text-slate-400">Method</label>
                        <select onchange="updateConfig(${index}, 'method', this.value)"
                            class="w-full bg-black/40 text-white p-2 rounded">
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
                                class="w-full bg-black/40 text-green-400 p-2 rounded font-mono"
                                rows="4"
                                placeholder="return input.amount * 2;">
                        ${step.config.code || ''}
                            </textarea>
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
                            class="w-full bg-black/40 text-white p-2 rounded">
                    </div>
                `;
            }

            // ================= CONDITION =================
            if (step.type === 'condition') {
                return `
                    <div>
                        <label class="text-xs text-slate-400">Expression</label>
                        <input type="text"
                            value="${step.config.expression || ''}"
                            oninput="updateConfig(${index}, 'expression', this.value)"
                            class="w-full bg-black/40 text-white p-2 rounded"
                            placeholder="payment.status == 'success'">
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div>
                            <label class="text-xs text-slate-400">True → Step ID</label>
                            <input type="text"
                                value="${step.branches?.true || ''}"
                                oninput="updateBranch(${index}, 'true', this.value)"
                                class="w-full bg-black/40 text-white p-2 rounded">
                        </div>

                        <div>
                            <label class="text-xs text-slate-400">False → Step ID</label>
                            <input type="text"
                                value="${step.branches?.false || ''}"
                                oninput="updateBranch(${index}, 'false', this.value)"
                                class="w-full bg-black/40 text-white p-2 rounded">
                        </div>
                    </div>
                `;
            }

            return '';
        }

        // Sync JSON to hidden input
        function syncJSON() {
            document.getElementById('definition').value = JSON.stringify({
                steps: steps
            }, null, 2);
        }

        function updateBranch(index, key, value) {
            if (!steps[index].branches) {
                steps[index].branches = {
                    true: null,
                    false: null
                };
            }

            steps[index].branches[key] = value;
            syncJSON();
        }

        async function submitWorkflow() {
            syncJSON(); // update DAG

            const payload = {
                name: document.querySelector('[name="name"]').value,
                description: document.getElementById('description').value,
                trigger_type: document.querySelector('[name="trigger_type"]').value,
                cron_expression: document.querySelector('[name="cron_expression"]')?.value || null,
                definition: document.getElementById('definition').value
            };

            console.log(payload);

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

                if (!response.ok) {
                    throw data;
                }

                // success
                alert("Workflow created successfully!");

                window.location.href = "/workflows";

            } catch (error) {
                console.error(error);

                alert(error.message || "Failed to create workflow");
            }
        }

        // initial render
        renderSteps();
    </script>
@endpush
