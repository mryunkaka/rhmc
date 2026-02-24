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
        Schema::create('farmasi_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type', 50);
            $table->unsignedInteger('medic_user_id')->nullable();
            $table->string('medic_name', 255);
            $table->text('description');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmasi_activities');
    }
};
