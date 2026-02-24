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
        Schema::create('user_farmasi_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type', 50);
            $table->string('message', 255);
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->useCurrent();

            $table->index('user_id', 'idx_user_id');
            $table->index('type', 'idx_type');
            $table->index('is_read', 'idx_is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_farmasi_notifications');
    }
};
