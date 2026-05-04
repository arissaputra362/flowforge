@extends('layouts.app')

@section('title', 'Workflows')
@section('page_title', 'Workflows')
@section('page_subtitle', 'Manage and monitor your workflows')

@section('header_actions')
    <a href="/workflows/create"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        + New Workflow
    </a>
@endsection

@section('content')
    <div class="glass p-6">
        <table id="workflowTable" class="w-full text-sm">
            <thead>
                <tr class="text-slate-400 border-b border-white/10">
                    <th class="text-left py-3">Name</th>
                    <th class="text-left py-3">Description</th>
                    <th class="text-left py-3">Version</th>
                    <th class="text-left py-3">Runs</th>
                    <th class="text-left py-3">Created</th>
                    <th class="text-right py-3">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        const TOKEN = "{{ session('api_token') }}"
        $(document).ready(function() {
            $('#workflowTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/workflows',
                    headers: {
                        Authorization: 'Bearer ' + TOKEN
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },

                    {
                        data: 'description',
                        render: function(data) {
                            return data ? data.substring(0, 60) + '...' : '—';
                        }
                    },

                    {
                        data: 'version',
                        render: function(data) {
                            return data ?
                                `<span class="px-2 py-1 rounded bg-indigo-500/20 text-indigo-300 text-xs">v${data}</span>` :
                                '—';
                        }
                    },

                    {
                        data: 'runs_count',
                        name: 'runs_count'
                    },

                    {
                        data: 'created_at',
                        render: function(data) {
                            return new Date(data).toLocaleDateString();
                        }
                    },

                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: function(id) {
                            return `
                        <div class="flex justify-end gap-2">
                            <a href="/workflows/${id}" 
                               class="px-3 py-1 rounded bg-white/5 hover:bg-white/10 text-xs">
                                View
                            </a>
                        </div>
                    `;
                        }
                    }
                ]
            });
        });

        function runWorkflow(id) {
            fetch(`/workflows/${id}/run`, {
                    method: 'POST',
                    headers: {
                        Authorization: 'Bearer ' + TOKEN
                    }
                })
                .then(res => res.json())
                .then(() => alert('Workflow started'))
                .catch(() => alert('Failed'));
        }
    </script>
@endpush
