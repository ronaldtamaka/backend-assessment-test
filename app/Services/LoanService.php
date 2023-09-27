<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;


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
        //
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);
        
        $amountValue = (int)($amount / $terms);
        $amountValueRound = round($amount / $terms);
        $divAmount = $amount - ($amountValue * 3 );

        $dueDate = Carbon::parse($processedAt);
        for ($i = 1; $i <= $terms; $i++) {
            $getAmount = $amountValue;
            if ( $amountValue != $amountValueRound && $i + $divAmount > $terms) 
                $getAmount = $amountValueRound;

            $dueDate = $dueDate->addMonths(1);

            $scheduledRepayment = ([
                'loan_id' => $loan->id,
                'amount' => $getAmount,
                'outstanding_amount' => $getAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE
            ]);
            ScheduledRepayment::create($scheduledRepayment);
            
        }
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
