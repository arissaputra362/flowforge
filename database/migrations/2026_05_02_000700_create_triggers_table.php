<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTriggersTable extends Migration
{
    public function up()
    {
        Schema::create('triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->string('type')->index();
            $table->json('config')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('triggers');
    }
}
