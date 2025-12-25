<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds cancellation_reason for cancelled box orders.
     */
    public function up(): void
    {
        Schema::table('box_orders', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_orders', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
