<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bans');
    }
};
