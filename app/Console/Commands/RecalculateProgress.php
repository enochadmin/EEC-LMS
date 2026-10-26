<?php

namespace App\Console\Commands;

use App\Models\ModuleProgress;
use App\Services\ProgressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateProgress extends Command
{
    protected $signature = 'lms:recalculate-progress
                            {--module= : Only rebuild a single training module id}
                            {--prune : Also delete progress rows whose learner is not enrolled in that module}';

    protected $description = 'Rebuild module progress from approved lessons and passed quizzes';

    public function handle(ProgressService $progress): int
    {
        if ($this->option('prune')) {
            $orphans = DB::table('module_progress')
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('assigned_modules')
                        ->whereColumn('assigned_modules.user_id', 'module_progress.user_id')
                        ->whereColumn('assigned_modules.training_module_id', 'module_progress.training_module_id');
                })
                ->delete();

            $this->warn("Pruned {$orphans} progress row(s) with no matching enrolment.");
        }

        if ($moduleId = $this->option('module')) {
            $count = $progress->recalculateForModule((int) $moduleId);
            $this->info("Recalculated {$count} learner(s) on module {$moduleId}.");

            return self::SUCCESS;
        }

        $count = $progress->recalculateAll();
        $this->info("Recalculated {$count} learner/module pair(s).");

        return self::SUCCESS;
    }
}