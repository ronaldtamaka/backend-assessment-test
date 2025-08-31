<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;

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
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'processed_at' => date('Y-m-d', strtotime($processedAt)),
            'status' => Loan::STATUS_DUE,
        ]);

        $baseInstallment = intdiv($amount, $terms);
        $remainingAmount = $amount - ($baseInstallment * ($terms - 1));

        for ($i = 1; $i <= $terms; $i++) {
            if ($i === $terms) {
                $installmentAmount = $remainingAmount;
            } else {
                $installmentAmount = $baseInstallment;
            }

            $loan->scheduledRepayments()->create([
                'amount' => $installmentAmount,
                'due_date' => date('Y-m-d', strtotime($processedAt . ' + ' . ($i) . ' months')),
                'status' => Loan::STATUS_DUE,
                'outstanding_amount' => $installmentAmount,
                'currency_code' => $currencyCode
            ]);
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
