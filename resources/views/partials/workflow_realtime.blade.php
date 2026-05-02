<div id="workflow-{{ $runId }}" data-run-id="{{ $runId }}">
    <h3>Workflow {{ $runId }} realtime</h3>
    <ul id="workflow-steps-{{ $runId }}"></ul>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const runId = '{{ $runId }}';
        Echo.channel('workflow.' + runId)
            .listen('StepStarted', (e) => {
                const li = document.createElement('li');
                li.id = 'step-' + e.step_run_id;
                li.textContent = `${e.step_id} - started (attempt ${e.attempt})`;
                document.getElementById('workflow-steps-' + runId).appendChild(li);
            })
            .listen('StepSucceeded', (e) => {
                const el = document.getElementById('step-' + e.step_run_id);
                if (el) el.textContent = `${e.step_id} - success`;
            })
            .listen('StepFailed', (e) => {
                const el = document.getElementById('step-' + e.step_run_id);
                if (el) el.textContent = `${e.step_id} - failed: ${e.error}`;
            });
    });
</script>
