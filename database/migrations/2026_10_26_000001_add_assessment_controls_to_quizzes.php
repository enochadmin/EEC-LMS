<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R8 — assessment controls.
 *
 * quizzes:             how the quiz behaves (timing, attempts, ordering)
 * quiz_attempts:       the lifecycle of one sitting (started → submitted)
 *
 * All columns are nullable or safely defaulted, so existing quizzes keep
 * working exactly as before until an instructor opts in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('passing_score');
            $table->unsignedSmallInteger('attempts_allowed')->default(0)->after('time_limit_minutes');
            $table->unsignedSmallInteger('cooldown_minutes')->default(0)->after('attempts_allowed');
            $table->boolean('shuffle_questions')->default(false)->after('cooldown_minutes');
            $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('attempt_number');
            $table->timestamp('expires_at')->nullable()->after('started_at');
            $table->timestamp('submitted_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'time_limit_minutes',
                'attempts_allowed',
                'cooldown_minutes',
                'shuffle_questions',
                'shuffle_options',
            ]);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'expires_at', 'submitted_at']);
        });
    }
};
