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
            // $table->unsignedInteger('loan_id');
            $table->foreignId('loan_id')->references('id')->on('loans');

            $table->date('received_date');
            $table->decimal('amount');
            // TODO: Add missing columns here

            $table->timestamps();
            $table->softDeletes();

            // $table->foreign('loan_id')
            //     ->references('id')
            //     ->on('loans')
            //     ->onUpdate('cascade')
            //     ->onDelete('restrict');
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
