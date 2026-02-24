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
        Schema::create('interview_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->integer('weight')->default(1);
            $table->boolean('is_active')->default(true);

            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_criteria');
    }
};
