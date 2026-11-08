<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 64)->default('demo');
            $table->enum('status', ['pending', 'submitted', 'failed'])->default('pending');
            $table->string('provider_reference')->nullable()->unique();
            $table->jsonb('payload');
            $table->timestamp('submitted_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_submissions');
    }
};
