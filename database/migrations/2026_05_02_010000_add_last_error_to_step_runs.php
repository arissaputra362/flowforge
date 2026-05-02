<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('step_runs', function (Blueprint $table) {
            $table->text('last_error')->nullable()->after('output');
        });
    }

    public function down(): void
    {
        Schema::table('step_runs', function (Blueprint $table) {
            $table->dropColumn('last_error');
        });
    }
};
