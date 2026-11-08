<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 100);
            $table->string('aggregate_type', 100);
            $table->unsignedBigInteger('aggregate_id');
            $table->jsonb('payload');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->useCurrent();
            $table->timestamp('claimed_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['published_at', 'failed_at', 'available_at']);
            $table->index(['claim_token', 'claimed_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
