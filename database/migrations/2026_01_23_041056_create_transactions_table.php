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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('sender_account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('receiver_account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['transfer', 'credit', 'debit']);
            $table->enum('status', ['success', 'failed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
