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
        Schema::create('user_spreadsheets', function (Blueprint $table) {
            $table->id();
            $table->integer('medic_user_id');
            $table->string('spreadsheet_id', 100);
            $table->text('spreadsheet_url');
            $table->dateTime('created_at')->useCurrent();

            $table->unique('medic_user_id', 'uniq_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_spreadsheets');
    }
};
