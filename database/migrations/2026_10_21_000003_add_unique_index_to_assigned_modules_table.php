<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard against duplicate enrollments (double-clicked approve/assign).
        // Verified no duplicates exist before adding the index.
        Schema::table('assigned_modules', function (Blueprint $table) {
            $table->unique(['training_module_id', 'user_id'], 'assigned_modules_module_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('assigned_modules', function (Blueprint $table) {
            $table->dropUnique('assigned_modules_module_user_unique');
        });
    }
};
