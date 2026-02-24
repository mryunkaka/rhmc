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
        Schema::create('identity_master', function (Blueprint $table) {
            $table->id();
            $table->string('citizen_id', 32);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('dob')->nullable();
            $table->enum('sex', ['M', 'F'])->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->onUpdateCurrent();
            $table->unsignedBigInteger('active_version_id')->nullable();

            $table->unique('citizen_id', 'uq_identity_citizen');
            $table->index(['first_name', 'last_name'], 'idx_identity_name');
            $table->index('active_version_id', 'fk_identity_active_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_master');
    }
};
