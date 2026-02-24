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
        Schema::create('user_rh', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 100);
            $table->string('citizen_id', 30)->nullable();
            $table->string('no_hp_ic', 20)->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('pin', 255);
            $table->string('api_token', 64)->nullable();
            $table->enum('role', ['Staff', 'Staff Manager', 'Manager', 'Vice Director', 'Director'])->default('Staff');
            $table->integer('batch')->nullable();
            $table->string('kode_nomor_induk_rs', 30)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->string('file_ktp', 255)->nullable();
            $table->string('file_sim', 255)->nullable();
            $table->string('file_kta', 255)->nullable();
            $table->string('file_skb', 255)->nullable();
            $table->string('sertifikat_heli', 255)->nullable();
            $table->string('sertifikat_operasi', 255)->nullable();
            $table->string('dokumen_lainnya', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->onUpdateCurrent();
            $table->boolean('is_active')->default(true);
            $table->text('resign_reason')->nullable();
            $table->integer('resigned_by')->nullable();
            $table->dateTime('resigned_at')->nullable();
            $table->dateTime('reactivated_at')->nullable();
            $table->integer('reactivated_by')->nullable();
            $table->text('reactivated_note')->nullable();

            $table->unique('full_name', 'uniq_user_name');
            $table->unique('api_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_rh');
    }
};
