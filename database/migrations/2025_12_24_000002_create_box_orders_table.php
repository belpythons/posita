<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('box_orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->foreignId('box_template_id')->constrained('box_templates')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('total_price', 12, 2);
            $table->datetime('pickup_datetime');
            $table->string('payment_proof_path')->nullable();
            $table->enum('status', ['pending', 'paid', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index('box_template_id');
            $table->index('status');
            $table->index('pickup_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_orders');
    }
};
