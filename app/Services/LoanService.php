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
        $createdLoan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);
        
        
        // Create Scheduled Repayments
        $remainAmount = $amount;
        $amountRepayPerMonth = $amount / $terms;

        $lastMonthRepayment = Carbon::parse($processedAt);
        for ($i = 1; $i <= $terms; $i++) {
            if ($i == $terms) {
                $amountRepayPerMonth = $remainAmount;
            }
            
            $createdLoan->scheduledRepayments()->create([
                'amount' => (int)$amountRepayPerMonth,
                'outstanding_amount' => (int)$amountRepayPerMonth,
                'currency_code' => $currencyCode,
                'due_date' => $lastMonthRepayment->addMonth()->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
            $remainAmount -= (int)$amountRepayPerMonth;
        }

        return $createdLoan;
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
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        return $receivedRepayment;
    }
}
