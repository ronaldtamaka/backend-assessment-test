<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        // Transaction
        DB::beginTransaction();
        // create loan and connect it to the user
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $eachPart = floor($amount / $terms);
        $eachPartModulus = $amount % $terms;

        // create scheduled repayments for the loan
        $monthDue = Carbon::parse($processedAt);
        $scheduledRepayments = [];
        for ($i = 0; $i < $terms; $i++) {
            $outstandingAmount = $eachPart+($eachPartModulus > 0 ? 1 : 0);
            $eachPartModulus--;
            $scheduledRepayments[] = [
                'loan_id' => $loan->id,
                'amount' => $outstandingAmount,
                'outstanding_amount' => $outstandingAmount,
                'currency_code' => $currencyCode,
                'due_date' => $monthDue->addMonth()->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }
        ScheduledRepayment::insert($scheduledRepayments);

        // commit transaction
        DB::commit();

        return $loan;

        
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        //
    }
}
