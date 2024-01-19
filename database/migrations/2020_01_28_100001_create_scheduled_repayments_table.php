<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_repayments', function (Blueprint $table) {
            $table->id();
            // $table->unsignedInteger('loan_id');
            $table->foreignId('loan_id')->references('id')->on('loans');
            $table->string('currency_code')->default('USD');
            $table->string('status')->default('due');

            $table->date('due_date');
            $table->decimal('amount');
            // TODO: Add missing columns here
            $table->decimal('outstanding_amount')->default(0);
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
        Schema::dropIfExists('scheduled_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
