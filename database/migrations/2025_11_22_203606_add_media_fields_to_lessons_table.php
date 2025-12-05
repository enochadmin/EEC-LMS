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
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('pdf_file_path')->nullable()->after('attachment_path');
            $table->string('youtube_link')->nullable()->after('pdf_file_path');
            // video_path already exists, we'll use it for MP4 files
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['pdf_file_path', 'youtube_link']);
        });
    }
};
