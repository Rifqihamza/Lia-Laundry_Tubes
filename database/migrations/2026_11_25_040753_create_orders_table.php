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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_code', 30)->unique();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('laundry_services');
            $table->decimal('weight_kg', 6, 2);
            $table->decimal('total_price', 12, 2);
            $table->enum('payment_method', ['cash', 'qris', 'pending'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('laundry_status', ['queued', 'processing', 'completed', 'picked_up'])->default('queued');
            $table->foreignUuid('confirmed_by')->constrained('users');
            $table->foreignUuid('processed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
