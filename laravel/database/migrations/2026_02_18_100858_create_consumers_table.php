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
        Schema::create('consumers', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('dob')->comment('Date of Birth');
            $table->enum('sex', ['male', 'female']);
            $table->string('nationality', 100)->default('Indonesia');
            $table->string('citizen_id', 50)->comment('Nomor Identitas / Citizen ID');
            $table->string('pekerjaan', 100)->default('Freelance');
            $table->string('registered_by', 100);
            $table->integer('registered_by_user_id')->unsigned();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->onUpdateCurrent();

            $table->unique('citizen_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumers');
    }
};
