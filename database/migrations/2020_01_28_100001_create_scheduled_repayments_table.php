<?php

use App\Models\ScheduledRepayment;
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
            $table->foreignId('loan_id')
                ->constrained('loans')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // TODO: Add missing columns here
            $table->integer('amount');
            $table->integer('outstanding_amount');
            $table->integer('paid_amount')->default(0);
            $table->string('currency_code', 3);
            $table->date('due_date');
            $table->enum('status', [
                ScheduledRepayment::STATUS_DUE,
                ScheduledRepayment::STATUS_PARTIAL,
                ScheduledRepayment::STATUS_REPAID,
            ])->default(ScheduledRepayment::STATUS_DUE);

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
        Schema::dropIfExists('scheduled_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
