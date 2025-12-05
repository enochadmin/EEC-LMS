<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_completions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved'])->default('approved')->after('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_completions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
