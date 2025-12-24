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
            $table->foreignId('shop_session_id')->constrained('shop_sessions')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('qty_initial');
            $table->integer('qty_sold')->default(0);
            $table->integer('qty_remaining')->default(0);
            $table->decimal('base_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('markup_percent')->default(10);
            $table->decimal('subtotal_income', 12, 2)->default(0); // qty_sold * selling_price
            $table->timestamps();

            $table->index('shop_session_id');
            $table->index('partner_id');
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
