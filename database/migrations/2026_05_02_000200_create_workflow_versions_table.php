<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowVersionsTable extends Migration
{
    public function up()
    {
        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id')->index();
            $table->string('version')->index();
            $table->json('dag');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete('cascade');
            $table->unique(['workflow_id', 'version']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_versions');
    }
}
