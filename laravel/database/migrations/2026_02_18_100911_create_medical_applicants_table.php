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
        Schema::create('medical_applicants', function (Blueprint $table) {
            $table->id();
            $table->string('ic_name', 150);
            $table->integer('ooc_age');
            $table->string('ic_phone', 30);
            $table->text('medical_experience')->nullable();
            $table->string('city_duration', 100)->nullable();
            $table->string('online_schedule', 150)->nullable();
            $table->text('other_city_responsibility')->nullable();
            $table->text('motivation')->nullable();
            $table->text('work_principle')->nullable();
            $table->enum('academy_ready', ['ya', 'tidak']);
            $table->text('academy_reason')->nullable();
            $table->enum('rule_commitment', ['ya', 'tidak']);
            $table->string('duty_duration', 150)->nullable();
            $table->enum('status', ['submitted', 'ai_test', 'ai_completed', 'interview', 'final_review', 'accepted', 'rejected'])->default('submitted');
            $table->enum('rejection_stage', ['ai', 'interview'])->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_applicants');
    }
};
