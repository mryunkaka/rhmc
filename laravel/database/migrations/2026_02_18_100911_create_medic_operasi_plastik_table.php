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
        Schema::create('medic_operasi_plastik', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->date('tanggal');
            $table->enum('jenis_operasi', ['Rekonstruksi Wajah', 'Suntik Putih']);
            $table->text('alasan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->integer('id_penanggung_jawab');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('id_penanggung_jawab', 'fk_oplas_penanggung');
            $table->index('id_user', 'idx_user');
            $table->index('tanggal', 'idx_tanggal');
            $table->index('approved_by', 'fk_oplas_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medic_operasi_plastik');
    }
};
