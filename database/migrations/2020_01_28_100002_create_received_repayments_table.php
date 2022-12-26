<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceivedRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('received_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');

            // TODO: Add missing columns here
            $table->unsignedBigInteger('scheduled_repayment_id');
            $table->date('pay_month');
            $table->string('status');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('loan_id')
                ->references('id')
                ->on('loans')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('scheduled_repayment_id')
                ->references('id')
                ->on('scheduled_repayments')
                ->onUpdate('cascade')
                ->onDelete('restrict');
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
        Schema::dropIfExists('received_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
