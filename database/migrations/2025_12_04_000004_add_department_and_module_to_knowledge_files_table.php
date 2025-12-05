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
        Schema::table('knowledge_files', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_files', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('uploaded_by')
                    ->constrained('departments')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('knowledge_files', 'training_module_id')) {
                $table->foreignId('training_module_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('training_modules')
                    ->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_files', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_files', 'training_module_id')) {
                $table->dropConstrainedForeignId('training_module_id');
            }

            if (Schema::hasColumn('knowledge_files', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
    }
};


