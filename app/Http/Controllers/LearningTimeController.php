<?php

namespace App\Http\Controllers;

use App\Models\AssignedModule;
use App\Models\LearningTimeLog;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Receives "still here" heartbeats from lesson and module pages.
 *
 * Only enrolled learners are counted. If an instructor or admin browses a
 * module, nothing is written — otherwise staff reading would show up as
 * learner engagement in the Reports tab.
 */
class LearningTimeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'training_module_id' => ['required', 'integer', 'exists:training_modules,id'],
            'lesson_id' => ['nullable', 'integer'],
            'seconds' => ['required', 'integer', 'min:1', 'max:300'],
        ]);

        $user = $request->user();

        $isEnrolled = AssignedModule::where('user_id', $user->id)
            ->where('training_module_id', $data['training_module_id'])
            ->exists();

        if (! $isEnrolled) {
            return response()->json(['tracked' => false], 202);
        }

        $lessonId = (int) ($data['lesson_id'] ?? 0);

        if ($lessonId > 0) {
            $belongs = Lesson::where('id', $lessonId)
                ->where('training_module_id', $data['training_module_id'])
                ->exists();

            if (! $belongs) {
                return response()->json(['tracked' => false], 202);
            }
        }

        $today = now()->toDateString();

        // Upsert + increment keeps one row per learner/module/lesson/day.
        $log = LearningTimeLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'training_module_id' => $data['training_module_id'],
                'lesson_id' => $lessonId,
                'tracked_on' => $today,
            ],
            ['seconds' => 0]
        );

        $log->increment('seconds', (int) $data['seconds']);
        $log->touch();

        // Keep the "last active" marker on module progress in sync, so reports
        // can show recency even for learners who only read.
        DB::table('module_progress')
            ->where('user_id', $user->id)
            ->where('training_module_id', $data['training_module_id'])
            ->update(['last_accessed_at' => now(), 'updated_at' => now()]);

        return response()->json(['tracked' => true, 'total_seconds' => $log->seconds]);
    }
}
