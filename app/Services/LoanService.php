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
        return Loan::create([
            'user_id' => $userId,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => now(),
            'status' => Loan::STATUS_DUE,
        ]);
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
        $scheduledRepayments = $loan->scheduledRepayments()->where('amount', '>', 0)->get();
    
        if ($scheduledRepayments->isEmpty()) {
            throw new \InvalidArgumentException('No scheduled repayments found for the loan.');
        }
    
        $repaymentAmount = $amount;
    
        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($repaymentAmount >= $scheduledRepayment->amount) {
                // Repay full scheduled repayment
                $repaymentAmount -= $scheduledRepayment->amount;
                $scheduledRepayment->update(['amount' => 0]);
            } else {
                // Repay partial scheduled repayment
                $scheduledRepayment->update(['amount' => $scheduledRepayment->amount - $repaymentAmount]);
                $repaymentAmount = 0;
            }
    
            // Create ReceivedRepayment record
            ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'scheduled_repayment_id' => $scheduledRepayment->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);
    
            // Update outstanding amount in the loan
            $loan->update(['outstanding_amount' => $loan->outstanding_amount - $amount]);
    
            if ($repaymentAmount <= 0) {
                break;
            }
        }
    
        return ReceivedRepayment::latest()->first();
    }
}
