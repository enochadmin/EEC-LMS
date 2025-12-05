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
        Schema::create('knowledge_files', function(Blueprint $t){
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('file_path');
            $t->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_files');
    }
};
