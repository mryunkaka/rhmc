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
        Schema::create('ai_test_results', function (Blueprint $table) {
            $table->id();
            $table->integer('applicant_id');
            $table->longText('answers_json');
            $table->integer('duration_seconds')->nullable();
            $table->integer('score_total')->nullable();
            $table->integer('consistency_score')->nullable();
            $table->integer('focus_score')->nullable();
            $table->integer('honesty_score')->default(0);
            $table->integer('attitude_score')->nullable();
            $table->integer('loyalty_score')->nullable();
            $table->integer('social_score')->nullable();
            $table->text('risk_flags')->nullable();
            $table->text('personality_summary')->nullable();
            $table->enum('decision', ['recommended', 'consider', 'not_recommended'])->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_test_results');
    }
};
