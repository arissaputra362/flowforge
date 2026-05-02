<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStepRunsTable extends Migration
{
    public function up()
    {
        Schema::create('step_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_run_id')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->string('step_id')->index();
            $table->integer('attempt')->default(0);
            $table->string('status')->index();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('workflow_run_id')->references('id')->on('workflow_runs')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('step_runs');
    }
}
