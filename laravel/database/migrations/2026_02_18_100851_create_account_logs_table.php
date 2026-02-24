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
        Schema::create('account_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('full_name_before', 100)->nullable();
            $table->string('full_name_after', 100)->nullable();
            $table->string('position_before', 100)->nullable();
            $table->string('position_after', 100)->nullable();
            $table->boolean('pin_changed')->default(false);
            $table->datetime('created_at')->nullable();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_logs');
    }
};
