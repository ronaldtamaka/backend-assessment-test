<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debit_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_card_id')
                ->constrained('debit_cards')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->integer('amount');
            $table->string('currency_code');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('debit_card_transactions');
        Schema::enableForeignKeyConstraints();
    }
}
