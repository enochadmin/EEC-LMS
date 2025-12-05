<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('user_answers', 'text_answer')) {
                $table->text('text_answer')->nullable()->after('selected_option_id');
            }
            if (!Schema::hasColumn('user_answers', 'points_awarded')) {
                $table->integer('points_awarded')->nullable()->after('text_answer');
            }
            if (!Schema::hasColumn('user_answers', 'is_graded')) {
                $table->boolean('is_graded')->default(false)->after('points_awarded');
            }
        });
    }

    public function down()
    {
        Schema::table('user_answers', function (Blueprint $table) {
            if (Schema::hasColumn('user_answers', 'is_graded')) {
                $table->dropColumn('is_graded');
            }
            if (Schema::hasColumn('user_answers', 'points_awarded')) {
                $table->dropColumn('points_awarded');
            }
            if (Schema::hasColumn('user_answers', 'text_answer')) {
                $table->dropColumn('text_answer');
            }
        });
    }
};
