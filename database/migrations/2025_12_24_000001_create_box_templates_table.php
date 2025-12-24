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
        Schema::create('box_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['heavy_meal', 'snack_box']);
            $table->decimal('price', 12, 2);
            $table->json('items_json'); // Array of items: ["Nasi", "Ayam", "Lalapan"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_templates');
    }
};
