<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyScheduledRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('outstanding_amount')->default(0);
            $table->string('currency_code')->nullable();
            $table->tinyInteger('terms')->default(0);
            $table->string('status')->nullable();
            $table->date('processed_at')->nullable();
            $table->date('due_date')->nullable();
        });

        Schema::table('received_repayments', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('currency_code')->nullable();
            $table->date('received_at')->nullable();
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
            $table->dropColumn('amount');
            $table->dropColumn('outstanding_amount');
            $table->dropColumn('currency_code');
            $table->dropColumn('terms');
            $table->dropColumn('status');
            $table->dropColumn('processed_at');
            $table->dropColumn('due_date');
        });

       Schema::table('received_repayments', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->dropColumn('currency_code');
            $table->dropColumn('received_at');
        });
    }
}