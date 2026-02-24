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
        Schema::create('ems_sales', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50);
            $table->string('service_detail', 100);
            $table->string('operasi_tingkat', 10)->nullable();
            $table->text('medicine_usage')->nullable();
            $table->string('patient_name', 100)->nullable();
            $table->string('location', 10)->nullable();
            $table->integer('qty')->default(1);
            $table->integer('price')->default(0);
            $table->integer('total')->default(0);
            $table->string('payment_type', 20)->nullable();
            $table->string('medic_name', 100);
            $table->string('medic_jabatan', 100);
            $table->dateTime('created_at')->useCurrent();
            $table->integer('billing_amount')->default(0);
            $table->integer('cash_amount')->default(0);
            $table->integer('doctor_share')->default(0);
            $table->integer('team_share')->default(0);

            $table->index('created_at', 'idx_date');
            $table->index('payment_type', 'idx_payment');
            $table->index('service_type', 'idx_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ems_sales');
    }
};
