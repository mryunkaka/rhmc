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
        Schema::create('salary', function (Blueprint $table) {
            $table->id();
            $table->string('medic_name', 100);
            $table->string('medic_jabatan', 100)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_transaksi')->default(0);
            $table->integer('total_item')->default(0);
            $table->integer('total_rupiah')->default(0);
            $table->integer('bonus_40')->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->string('paid_by', 100)->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['medic_name', 'period_start', 'period_end'], 'uniq_salary');
            $table->index(['period_start', 'period_end'], 'idx_period');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary');
    }
};
