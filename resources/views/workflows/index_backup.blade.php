@extends('layouts.app')

@section('title', 'Workflows')
@section('meta_description', 'Manage and monitor your FlowForge workflows')

@section('content')
<div class="fade-in">
    <div class="page-header">
        <h1>Workflows</h1>
    </div>

    @if($workflows->isEmpty())
        <div class="glass" style="padding:48px;text-align:center;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:48px;height:48px;color:#4b5563;margin:0 auto 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-3.75"/>
            </svg>
            <p style="color:#94a3b8;font-size:.95rem;">No workflows found. Create one via the API to get started.</p>
        </div>
    @else
        <div class="glass overflow-hidden">
            <table class="ff-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Latest Version</th>
                        <th>Runs</th>
                        <th>Created</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workflows as $workflow)
                    <tr>
                        <td>
                            <a href="/workflows/{{ $workflow->id }}" style="color:#a5b4fc;text-decoration:none;font-weight:600;">
                                {{ $workflow->name }}
                            </a>
                        </td>
                        <td class="text-muted text-sm">{{ Str::limit($workflow->description, 60) ?? '—' }}</td>
                        <td>
                            @if($workflow->latestVersion)
                                <span class="badge" style="background:rgba(99,102,241,.15);color:#a5b4fc;">
                                    v{{ $workflow->latestVersion->version }}
                                </span>
                            @else
                                <span class="text-muted text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $workflow->runs_count ?? 0 }}</td>
                        <td class="text-muted text-xs">{{ $workflow->created_at->diffForHumans() }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <a href="/workflows/{{ $workflow->id }}" class="btn btn-sm" style="background:rgba(255,255,255,.06);color:#cbd5e1;">
                                    View
                                </a>
                                <form method="POST" action="/workflows/{{ $workflow->id }}/run" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        ▶ Run
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($workflows->hasPages())
        <div class="pagination">
            {{ $workflows->links() }}
        </div>
        @endif
    @endif
</div>
@endsection
