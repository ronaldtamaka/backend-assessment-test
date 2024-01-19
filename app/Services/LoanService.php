<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
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
            'processed_at' => $processedAt,
        ]);

        // Create scheduled repayments
        $installmentAmount = $amount / $terms;
        $dueDate = \Carbon\Carbon::parse($processedAt)->addMonth();

        for ($i = 0; $i < $terms; $i++) {
            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $installmentAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate,
            ]);

            $dueDate->addMonth();
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
        // Find the next scheduled repayment
        $nextRepayment = $loan->scheduledRepayments()->where('paid_at', null)->first();

        if (!$nextRepayment) {
            // No more scheduled repayments
            throw new \Exception('No more scheduled repayments for this loan.');
        }

        // Determine the actual repayment amount (partial or full)
        $repaymentAmount = min($amount, $nextRepayment->amount);

        // Check if the repayment amount exceeds the outstanding amount
        if ($repaymentAmount > $nextRepayment->amount) {
            throw new \Exception('Repayment amount exceeds the outstanding amount.');
        }

        // Mark the scheduled repayment as paid
        $nextRepayment->update([
            'amount' => $repaymentAmount,
            'currency_code' => $currencyCode,
            'paid_at' => $receivedAt,
        ]);

        // Create a received repayment record
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'scheduled_repayment_id' => $nextRepayment->id,
            'amount' => $repaymentAmount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        return $receivedRepayment;
    }
}
