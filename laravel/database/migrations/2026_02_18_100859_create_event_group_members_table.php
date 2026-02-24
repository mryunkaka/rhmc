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
        Schema::create('event_group_members', function (Blueprint $table) {
            $table->id();
            $table->integer('event_group_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['event_group_id', 'user_id'], 'uniq_group_user');
            $table->index('event_group_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_group_members');
    }
};
