<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            if (! Schema::hasColumn('module_progress', 'completed_quizzes')) {
                $table->unsignedInteger('completed_quizzes')->default(0)->after('completed_lessons');
            }
            if (! Schema::hasColumn('module_progress', 'total_quizzes')) {
                $table->unsignedInteger('total_quizzes')->default(0)->after('completed_quizzes');
            }
            if (! Schema::hasColumn('module_progress', 'computed_at')) {
                $table->timestamp('computed_at')->nullable()->after('last_accessed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            foreach (['computed_at', 'total_quizzes', 'completed_quizzes'] as $column) {
                if (Schema::hasColumn('module_progress', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
