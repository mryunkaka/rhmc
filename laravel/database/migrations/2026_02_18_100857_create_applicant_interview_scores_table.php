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
        Schema::create('applicant_interview_scores', function (Blueprint $table) {
            $table->id();
            $table->integer('applicant_id')->unsigned();
            $table->integer('hr_id')->unsigned();
            $table->integer('criteria_id')->unsigned();
            $table->tinyInteger('score')->comment('1-5');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(['applicant_id', 'hr_id', 'criteria_id'], 'uniq_hr_candidate_criteria');
            $table->index('criteria_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_interview_scores');
    }
};
