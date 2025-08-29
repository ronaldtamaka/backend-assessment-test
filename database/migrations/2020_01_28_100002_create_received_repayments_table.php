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
            $table->foreignId('loan_id')
                ->constrained('loans')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // TODO: Add missing columns here
            $table->integer('amount');
            $table->string('currency_code', 3);
            $table->date('received_at');

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
        Schema::dropIfExists('received_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
