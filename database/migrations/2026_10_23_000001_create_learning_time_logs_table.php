<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggregate table for "time on course".
 *
 * One row = one learner, one module, one lesson (0 = module overview page)
 * on one calendar day. The browser sends heartbeats; the API increments
 * `seconds` on the matching row instead of inserting a new row per beat,
 * so the table stays small and report queries stay cheap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            // 0 instead of NULL so the unique index still applies to overview time.
            $table->unsignedBigInteger('lesson_id')->default(0);
            $table->unsignedInteger('seconds')->default(0);
            $table->date('tracked_on');
            $table->timestamps();

            $table->unique(
                ['user_id', 'training_module_id', 'lesson_id', 'tracked_on'],
                'learning_time_unique_day'
            );
            $table->index(['training_module_id', 'tracked_on']);
            $table->index(['user_id', 'tracked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_time_logs');
    }
};
