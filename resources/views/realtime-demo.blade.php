<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workflow Realtime Demo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-4xl p-8">
        <h1 class="text-3xl font-bold">Workflow Realtime Demo</h1>
        <p class="mt-2 text-slate-600">Listening to channel: <strong>workflow.{{ $runId }}</strong></p>

        <div class="mt-6 rounded-lg bg-white p-4 shadow">
            <div class="mb-3 text-sm text-slate-500">Events</div>
            <ul id="events" class="space-y-2"></ul>
        </div>
    </main>

    <script>
        const runId = @json($runId);
        const eventsEl = document.getElementById('events');

        const pushEvent = (title, payload) => {
            const item = document.createElement('li');
            item.className = 'rounded border border-slate-200 bg-slate-50 p-3 text-sm';
            item.textContent = `${new Date().toISOString()} | ${title} | ${JSON.stringify(payload)}`;
            eventsEl.prepend(item);
        };

        if (!window.Echo) {
            pushEvent('Echo not configured', { hint: 'Set VITE_PUSHER_APP_KEY and rebuild assets' });
        } else {
            window.Echo.channel(`workflow.${runId}`)
                .listen('WorkflowStarted', (e) => pushEvent('WorkflowStarted', e))
                .listen('StepStarted', (e) => pushEvent('StepStarted', e))
                .listen('StepSucceeded', (e) => pushEvent('StepSucceeded', e))
                .listen('StepFailed', (e) => pushEvent('StepFailed', e))
                .listen('WorkflowCompleted', (e) => pushEvent('WorkflowCompleted', e));
        }
    </script>
</body>
</html>
