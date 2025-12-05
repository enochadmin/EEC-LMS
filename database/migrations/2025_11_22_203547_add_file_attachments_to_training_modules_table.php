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
        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('pdf_file_path')->nullable()->after('description');
            $table->string('video_file_path')->nullable()->after('pdf_file_path');
            $table->string('youtube_link')->nullable()->after('video_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropColumn(['pdf_file_path', 'video_file_path', 'youtube_link']);
        });
    }
};
