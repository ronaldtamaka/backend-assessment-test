<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use App\Models\ScheduledRepayment;

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
    public function repayLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): ReceivedRepayment
    {
        //
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $dueDate = \Carbon\Carbon::parse($processedAt)->addMonths(1);
        for ($i = 1; $i <= $terms; $i++) {
            $getAmount = ($i != $terms) ? floor($amount / $terms) : ceil($amount / $terms);
            $scheduledRepayment = ([
                'loan_id' => $loan->id,
                'amount' => $getAmount,
                'outstanding_amount' => $getAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE
            ]);
            ScheduledRepayment::create($scheduledRepayment);
            $dueDate = $dueDate->addMonths(1);
        }
        return $loan;
    }
}
