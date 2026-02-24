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
        Schema::create('user_farmasi_status', function (Blueprint $table) {
            $table->integer('user_id')->primary();
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->dateTime('last_activity_at');
            $table->dateTime('last_confirm_at')->nullable();
            $table->dateTime('auto_offline_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->onUpdateCurrent();

            $table->index('status', 'idx_status');
            $table->index('last_activity_at', 'idx_last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_farmasi_status');
    }
};
