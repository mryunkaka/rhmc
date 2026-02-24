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
        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('applicant_id')->unsigned();
            $table->enum('document_type', ['ktp_ic', 'skb', 'sim', 'lainnya']);
            $table->string('file_path', 255);
            $table->boolean('is_valid')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamp('uploaded_at')->nullable()->useCurrent();

            $table->index('applicant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_documents');
    }
};
