@extends('layouts.app')

@section('title', 'Users')
@section('page_title', 'Users')
@section('page_subtitle', 'Manage and monitor your Users')

@section('header_actions')
    <a href="/users/create"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition">
        + New User
    </a>
@endsection

@section('content')
    <div class="glass p-6">
        <table id="usersTable" class="w-full text-sm">
            <thead>
                <tr class="text-slate-400 border-b border-white/10">
                    <th class="text-left py-3">Name</th>
                    <th class="text-left py-3">Email</th>
                    <th class="text-left py-3">Role</th>
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
            $('#usersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/users',
                    headers: {
                        Authorization: 'Bearer ' + TOKEN
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },

                    {
                        data: 'email',
                        name: 'email'
                    },

                    {
                        data: 'role',
                        name: 'role'
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
                            <a href="/users/${id}"
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
    </script>
@endpush
