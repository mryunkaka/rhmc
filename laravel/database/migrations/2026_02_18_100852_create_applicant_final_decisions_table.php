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
        Schema::create('applicant_final_decisions', function (Blueprint $table) {
            $table->id();
            $table->integer('applicant_id')->unsigned();
            $table->enum('system_result', ['lolos', 'tidak_lolos']);
            $table->boolean('overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->enum('final_result', ['lolos', 'tidak_lolos']);
            $table->string('decided_by', 100);
            $table->timestamp('decided_at')->nullable()->useCurrent();

            $table->unique('applicant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_final_decisions');
    }
};
