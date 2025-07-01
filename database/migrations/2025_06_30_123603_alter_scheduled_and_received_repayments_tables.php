<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to scheduled_repayments table
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            $table->integer('amount')->after('loan_id');
            $table->integer('outstanding_amount')->after('amount');
            $table->string('currency_code')->after('outstanding_amount');
            $table->date('due_date')->after('currency_code');
            $table->string('status')->after('due_date');
        });

        // Rename received_payments to received_repayments
        if (Schema::hasTable('received_payments')) {
            Schema::rename('received_payments', 'received_repayments');
        }

        // Add columns to received_repayments table
        Schema::table('received_repayments', function (Blueprint $table) {
            $table->integer('amount')->after('loan_id');
            $table->string('currency_code')->after('amount');
            $table->date('received_at')->after('currency_code');
        });
    }

    public function down(): void
    {
        // Remove columns from scheduled_repayments
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            $table->dropColumn(['amount', 'outstanding_amount', 'currency_code', 'due_date', 'status']);
        });

        // Remove columns from received_repayments
        Schema::table('received_repayments', function (Blueprint $table) {
            $table->dropColumn(['amount', 'currency_code', 'received_at']);
        });

        // Rename back received_repayments to received_payments
        if (Schema::hasTable('received_repayments')) {
            Schema::rename('received_repayments', 'received_payments');
        }
    }
};

