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
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'term_months' => $terms,
            'start_date' => Carbon::parse($processedAt),
        ]);

        // Create scheduled repayments
        $this->createScheduledRepayments($loan);
    }
    protected function createScheduledRepayments(Loan $loan)
    {
        $amountPerRepayment = $loan->amount / $loan->term_months;

        for ($i = 1; $i <= $loan->term_months; $i++) {
            $dueDate = $loan->start_date->copy()->addMonths($i);
            
            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amountPerRepayment,
                'due_date' => $dueDate,
            ]);
        }
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
         // Find the earliest scheduled repayment
         $scheduledRepayment = $loan->scheduledRepayments()->where('due_date', '<=', Carbon::parse($receivedAt))->first();

         if (!$scheduledRepayment) {
             // No scheduled repayment found
             // You may want to handle this case differently based on your business logic
             throw new \Exception('No scheduled repayment found for the given date.');
         }
 
         // Create received repayment
         $receivedRepayment = ReceivedRepayment::create([
             'loan_id' => $loan->id,
             'scheduled_repayment_id' => $scheduledRepayment->id,
             'amount' => $amount,
             'currency_code' => $currencyCode,
             'received_at' => Carbon::parse($receivedAt),
         ]);
 
         // Update the remaining amount for the scheduled repayment
         $scheduledRepayment->update(['remaining_amount' => max(0, $scheduledRepayment->remaining_amount - $amount)]);
 
         return $receivedRepayment;
    }
}
