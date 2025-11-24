<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToScheduleRepayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            //
            $table->integer('amount');
            $table->integer('outstanding_amount');
            $table->string('currency_code');
            $table->date('due_date');
            $table->string('status');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            //
            $table->dropColumn([
                'amount',
                'outstanding_amount',
                'currency_code',
                'due_date',
                'status'
            ]);
        });
    }
}
