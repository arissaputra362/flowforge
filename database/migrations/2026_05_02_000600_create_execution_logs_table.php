<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionLogsTable extends Migration
{
    public function up()
    {
        Schema::create('execution_logs', function (Blueprint $table) {
            $table->bigIncrements('seq');
            $table->uuid('workflow_run_id')->index();
            $table->uuid('step_run_id')->nullable()->index();
            $table->string('level')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('workflow_run_id')->references('id')->on('workflow_runs')->onDelete('cascade');
            $table->foreign('step_run_id')->references('id')->on('step_runs')->onDelete('set null');
            $table->index(['workflow_run_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('execution_logs');
    }
}
