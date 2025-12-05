<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('module_progress', 'lesson_percentage')) {
                $table->float('lesson_percentage')->default(0)->after('total_lessons');
            }
            if (!Schema::hasColumn('module_progress', 'quiz_percentage')) {
                $table->float('quiz_percentage')->default(0)->after('lesson_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            if (Schema::hasColumn('module_progress', 'quiz_percentage')) {
                $table->dropColumn('quiz_percentage');
            }
            if (Schema::hasColumn('module_progress', 'lesson_percentage')) {
                $table->dropColumn('lesson_percentage');
            }
        });
    }
};


