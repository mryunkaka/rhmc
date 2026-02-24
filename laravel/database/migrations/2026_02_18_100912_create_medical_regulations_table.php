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
        Schema::create('medical_regulations', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50);
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('location', 50)->nullable();
            $table->enum('price_type', ['FIXED', 'RANGE']);
            $table->integer('price_min')->default(0);
            $table->integer('price_max')->default(0);
            $table->enum('payment_type', ['CASH', 'INVOICE', 'BILLING']);
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_regulations');
    }
};
