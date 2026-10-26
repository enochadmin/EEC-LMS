<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course ownership and learner support.
 *
 *   training_modules.department_id  who the course belongs to (nullable, so
 *                                   existing courses keep working)
 *   module_advisors                 instructors/advisors a learner can ask for
 *                                   help; any number per course
 *   users.*                         the profile fields HR actually needs when
 *                                   onboarding: title, phone, office, type
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('category_id')
                ->constrained('departments')->nullOnDelete();
        });

        Schema::create('module_advisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['training_module_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('department_id');
            $table->string('phone', 32)->nullable()->after('job_title');
            $table->string('office_location')->nullable()->after('phone');
            $table->string('employment_type', 32)->nullable()->after('office_location');
            $table->date('hire_date')->nullable()->after('employment_type');
            $table->foreignId('manager_id')->nullable()->after('hire_date')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn(['job_title', 'phone', 'office_location', 'employment_type', 'hire_date']);
        });

        Schema::dropIfExists('module_advisors');

        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
