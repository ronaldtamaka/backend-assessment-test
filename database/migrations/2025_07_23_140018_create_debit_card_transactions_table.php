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
        Schema::create('debit_card_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('debit_card_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('description')->nullable();
            $table->timestamp('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_card_transactions');
    }
};
