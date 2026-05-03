@extends('layouts.app')

@section('title', $workflow->name)
@section('meta_description', 'Workflow detail: ' . $workflow->name)

@section('content')
<div class="fade-in">
    {{-- Breadcrumb --}}
    <div style="margin-bottom:8px;">
        <a href="/workflows" style="color:#64748b;text-decoration:none;font-size:.8rem;">← Back to Workflows</a>
    </div>

    <div class="page-header">
        <h1>{{ $workflow->name }}</h1>
        <form method="POST" action="/workflows/{{ $workflow->id }}/run" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-primary">▶ Run Workflow</button>
        </form>
    </div>

    @if($workflow->description)
    <p style="color:#94a3b8;margin-bottom:24px;">{{ $workflow->description }}</p>
    @endif

    {{-- Info Cards --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="glass" style="padding:20px;">
            <div class="text-xs text-muted" style="margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em;">Versions</div>
            <div style="font-size:1.5rem;font-weight:700;">{{ $workflow->versions->count() }}</div>
        </div>
        <div class="glass" style="padding:20px;">
            <div class="text-xs text-muted" style="margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em;">Total Runs</div>
            <div style="font-size:1.5rem;font-weight:700;">{{ $runs->total() }}</div>
        </div>
        <div class="glass" style="padding:20px;">
            <div class="text-xs text-muted" style="margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em;">Latest Version</div>
            <div style="font-size:1.5rem;font-weight:700;">
                v{{ $workflow->latestVersion->version ?? '—' }}
            </div>
        </div>
    </div>

    {{-- DAG Structure --}}
    @if($workflow->latestVersion && $workflow->latestVersion->definition)
    <div class="glass mb-6" style="padding:24px;">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;color:#c7d2fe;">DAG Structure (Latest Version)</h2>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            @foreach($workflow->latestVersion->definition['steps'] ?? [] as $step)
            <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:12px;padding:14px 18px;min-width:180px;">
                <div style="font-weight:600;color:#a5b4fc;margin-bottom:4px;">{{ $step['id'] }}</div>
                <div class="text-xs text-muted">Type: {{ $step['type'] ?? 'unknown' }}</div>
                @if(!empty($step['depends_on']))
                <div class="text-xs mt-2" style="color:#64748b;">
                    Depends: {{ implode(', ', $step['depends_on']) }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Runs History --}}
    <div class="glass overflow-hidden">
        <div style="padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);">
            <h2 style="font-size:1rem;font-weight:600;color:#c7d2fe;">Run History</h2>
        </div>
        @if($runs->isEmpty())
            <div style="padding:32px;text-align:center;color:#64748b;">
                No runs yet. Click "Run Workflow" to start.
            </div>
        @else
        <table class="ff-table">
            <thead>
                <tr>
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
                    <td class="text-sm" style="font-family:monospace;color:#a5b4fc;">
                        {{ Str::limit($run->id, 12) }}
                    </td>
                    <td><span class="badge badge-{{ $run->status }}">{{ $run->status }}</span></td>
                    <td class="text-sm">{{ $run->step_runs_count ?? 0 }}</td>
                    <td class="text-muted text-xs">{{ $run->created_at->diffForHumans() }}</td>
                    <td style="text-align:right;">
                        <a href="/runs/{{ $run->id }}" class="btn btn-sm" style="background:rgba(255,255,255,.06);color:#cbd5e1;">
                            Monitor →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($runs->hasPages())
        <div class="pagination" style="padding:16px;">
            {{ $runs->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
