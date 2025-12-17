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
        Schema::create('daily_consignments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('manual_partner_name')->nullable();
            $table->string('product_name');
            $table->integer('initial_stock');
            $table->decimal('base_price', 10, 2);
            $table->integer('markup_percentage');
            $table->decimal('selling_price', 10, 2);
            $table->integer('remaining_stock')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('total_profit', 10, 2)->default(0);
            $table->enum('status', ['open', 'closed']);
            $table->enum('disposition', ['returned', 'donated'])->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('input_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_consignments');
    }
};
