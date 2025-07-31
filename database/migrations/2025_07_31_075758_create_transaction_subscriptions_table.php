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
        Schema::create('transaction_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_key', 60);
            $table->unsignedSmallInteger('hit_count');
            $table->unsignedBigInteger('avg_amount');
            $table->date('last_seen_at');
            $table->timestamps();

            $table->unique(['user_id', 'merchant_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_subscriptions');
    }
};
