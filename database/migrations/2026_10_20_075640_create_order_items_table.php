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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sku_id')->nullable()->constrained('skus')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price_at_purchase');
            $table->string('product_name');
            $table->string('sku_code');
            $table->string('variant_name');
            $table->jsonb('variant_attributes')->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->enum('quantity_unit', ['piece', 'gram', 'millilitre'])->default('piece');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
