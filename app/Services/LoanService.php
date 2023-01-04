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
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        // generate scheduled repayments
        $scheduledRepayments = [];

        for ($i = 1; $i <= $terms; $i++) {
            $dueDate = date('Y-m-d', strtotime($processedAt . ' + ' . $i . ' month'));

            $amountTerm = floor($amount / $terms);

            if ($i === $terms) {
                $amountTerm = $amount - ($amountTerm * ($terms - 1));
            }

            $scheduledRepayments[] = [
                'amount' => $amountTerm,
                'outstanding_amount' => $amountTerm,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate,
            ];
        }

        $loan->scheduledRepayments()->createMany($scheduledRepayments);

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
