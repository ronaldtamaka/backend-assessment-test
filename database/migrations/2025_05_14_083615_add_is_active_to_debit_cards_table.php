<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToDebitCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debit_cards', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
        });
    }

    public function down()
    {
        Schema::table('debit_cards', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
