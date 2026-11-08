<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['reserved', 'captured', 'released'])->default('reserved');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
            $table->index(['wallet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_reservations');
    }
};
