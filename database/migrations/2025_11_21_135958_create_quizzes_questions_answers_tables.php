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
        Schema::create('quizzes', function(Blueprint $t){
            $t->id();
            $t->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $t->string('title')->nullable();
            $t->integer('passing_score')->default(70);
            $t->timestamps();
          });

          Schema::create('questions', function(Blueprint $t){
            $t->id();
            $t->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $t->text('question');
            $t->integer('points')->default(1);
            $t->timestamps();
          });

          Schema::create('question_options', function(Blueprint $t){
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('option_text');
            $t->boolean('is_correct')->default(false);
            $t->timestamps();
          });

          Schema::create('user_answers', function(Blueprint $t){
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $t->timestamps();
          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes_questions_answers_tables');
    }
};
