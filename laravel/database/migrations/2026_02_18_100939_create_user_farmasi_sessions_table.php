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
        Schema::create('user_farmasi_sessions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('medic_name', 100);
            $table->string('medic_jabatan', 100)->nullable();
            $table->dateTime('session_start');
            $table->dateTime('session_end')->nullable();
            $table->integer('duration_seconds')->nullable()->comment('Durasi sesi dalam detik');
            $table->enum('end_reason', ['manual_offline', 'auto_offline', 'force_offline', 'system'])->nullable();
            $table->integer('ended_by_user_id')->nullable()->comment('Jika force / manual oleh orang lain');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->onUpdateCurrent();

            $table->index(['user_id', 'session_start'], 'idx_user_date');
            $table->index(['session_start', 'session_end'], 'idx_session_range');
            $table->index('end_reason', 'idx_end_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_farmasi_sessions');
    }
};
