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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('consumer_name', 100);
            $table->unsignedBigInteger('consumer_id')->nullable();
            $table->string('medic_name', 100);
            $table->integer('medic_user_id')->nullable();
            $table->string('medic_jabatan', 50);
            $table->integer('package_id');
            $table->string('package_name', 100);
            $table->integer('price');
            $table->integer('qty_bandage')->default(0);
            $table->integer('qty_ifaks')->default(0);
            $table->integer('qty_painkiller')->default(0);
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->char('tx_hash', 64);
            $table->integer('identity_id')->nullable();
            $table->boolean('synced_to_sheet')->default(false);

            $table->unique('tx_hash', 'uniq_tx_hash');
            $table->index(['consumer_name', 'created_at'], 'idx_consumer_date');
            $table->index('package_id', 'fk_sales_package');
            $table->index('consumer_name', 'idx_consumer_name');
            $table->index('consumer_id', 'fk_sales_consumer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
