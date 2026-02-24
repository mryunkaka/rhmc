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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('reimbursement_code', 50)->comment('1 kode bisa banyak item');
            $table->enum('billing_source_type', ['instansi', 'restoran', 'toko', 'vendor', 'lainnya']);
            $table->string('billing_source_name', 255);
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2);
            $table->string('receipt_file', 255)->nullable();
            $table->enum('status', ['draft', 'submitted', 'paid', 'rejected'])->default('draft');
            $table->unsignedBigInteger('created_by')->comment('User pengaju');
            $table->unsignedBigInteger('paid_by')->nullable()->comment('User yang membayarkan (manager)');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('payment_note')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->onUpdateCurrent();

            $table->index('reimbursement_code', 'idx_reimbursement_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
