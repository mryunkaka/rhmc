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
        Schema::create('applicant_interview_results', function (Blueprint $table) {
            $table->id();
            $table->integer('applicant_id')->unsigned();
            $table->integer('total_hr');
            $table->decimal('average_score', 5, 2);
            $table->enum('final_grade', ['sangat_buruk', 'buruk', 'sedang', 'baik', 'sangat_baik']);
            $table->text('final_notes')->nullable();
            $table->text('hr_notes')->nullable();
            $table->text('ml_flags')->nullable();
            $table->decimal('ml_confidence', 5, 2)->nullable();
            $table->text('ml_notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('calculated_at')->nullable()->useCurrent();

            $table->unique('applicant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_interview_results');
    }
};
