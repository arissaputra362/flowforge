<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_runs', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at'], 'workflow_runs_tenant_created_at_idx');
            $table->index(['tenant_id', 'status', 'created_at'], 'workflow_runs_tenant_status_created_at_idx');
        });

        Schema::table('step_runs', function (Blueprint $table) {
            $table->index(['workflow_run_id', 'status'], 'step_runs_run_status_idx');
            $table->index(['status', 'workflow_run_id', 'step_id'], 'step_runs_status_run_step_idx');
        });

        Schema::table('execution_logs', function (Blueprint $table) {
            $table->index(['workflow_run_id', 'seq'], 'execution_logs_run_seq_idx');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_runs', function (Blueprint $table) {
            $table->dropIndex('workflow_runs_tenant_created_at_idx');
            $table->dropIndex('workflow_runs_tenant_status_created_at_idx');
        });

        Schema::table('step_runs', function (Blueprint $table) {
            $table->dropIndex('step_runs_run_status_idx');
            $table->dropIndex('step_runs_status_run_step_idx');
        });

        Schema::table('execution_logs', function (Blueprint $table) {
            $table->dropIndex('execution_logs_run_seq_idx');
        });
    }
};
