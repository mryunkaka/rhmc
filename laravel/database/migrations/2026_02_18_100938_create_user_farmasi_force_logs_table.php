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
        Schema::create('user_farmasi_force_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('target_user_id');
            $table->integer('forced_by_user_id');
            $table->text('reason');
            $table->dateTime('forced_at');
            $table->dateTime('created_at')->useCurrent();

            $table->index('target_user_id');
            $table->index('forced_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_farmasi_force_logs');
    }
};
