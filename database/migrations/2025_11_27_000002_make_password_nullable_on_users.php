<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Using raw SQL for MySQL to avoid requiring doctrine/dbal
        DB::statement("ALTER TABLE `users` MODIFY `password` VARCHAR(255) NULL;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make password NOT NULL again, setting empty string as default to avoid errors when nulls exist.
        DB::statement("ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL DEFAULT '';");
    }
};
