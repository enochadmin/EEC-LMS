<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lesson completions are reviewed by an instructor: they need a rejection
     * state that survives the review (so the learner can see why and resubmit)
     * and an audit trail of who reviewed them.
     *
     * The original enum('pending','approved') could not store 'rejected', which
     * is why rejection used to delete the row outright.
     */
    public function up(): void
    {
        Schema::table('lesson_completions', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });

        Schema::table('lesson_completions', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_completions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('lesson_completions', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('lesson_completions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_completions', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_completions', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('lesson_completions', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('lesson_completions', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });

        // status stays a string on rollback: shrinking it back to an enum would
        // truncate any 'rejected' rows that still exist.
    }
};
