<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount_cents');
            $table->string('currency')->default('usd');
            $table->text('note')->nullable();
            $table->string('payment_intent_id')->nullable()->unique();
            $table->string('charge_id')->nullable();
            $table->foreignId('original_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->dateTime('captured_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
