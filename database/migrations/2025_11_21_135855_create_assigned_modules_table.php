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
        Schema::create('assigned_modules', function(Blueprint $table){
            $table->id();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // assignee
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->date('due_date')->nullable();
            $table->enum('status',['assigned','in_progress','completed','overdue'])->default('assigned');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_modules');
    }
};
