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
        Schema::create('identity_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->string('citizen_id', 32);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('dob')->nullable();
            $table->enum('sex', ['M', 'F'])->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->text('change_reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index('identity_id', 'idx_versions_identity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_versions');
    }
};
