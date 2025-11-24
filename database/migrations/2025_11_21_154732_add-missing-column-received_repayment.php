<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnReceivedRepayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('received_repayments', function (Blueprint $table) {
            $table->integer('amount');
            $table->string('currency_code');
            $table->date('received_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('received_repayments', function (Blueprint $table) {
            $table->dropColumn(['amount', 'currency_code', 'received_at']);
        });
    }
}
