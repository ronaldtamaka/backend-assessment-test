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
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $processedDate = Carbon::parse($processedAt);
        
        $baseAmount = intval(floor($amount / $terms));
        $remainder = $amount % $terms;
        
        for ($i = 1; $i <= $terms; $i++) {
            $dueDate = $processedDate->copy()->addMonths($i);
            
            
            
            $repaymentAmount = $baseAmount + ($i <= $remainder ? 1 : 0);

            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $repaymentAmount,
                'outstanding_amount' => $repaymentAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
        }

        return $loan->load('scheduledRepayments');
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return Loan
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        
        ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        
        $scheduledRepayments = $loan->scheduledRepayments()
            ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
            ->orderBy('due_date')
            ->get();

        $remainingAmount = $amount;

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($remainingAmount <= 0) {
                break;
            }

            $outstandingAmount = $scheduledRepayment->outstanding_amount;
            
            if ($remainingAmount >= $outstandingAmount) {
                
                $scheduledRepayment->update([
                    'outstanding_amount' => 0,
                    'status' => ScheduledRepayment::STATUS_REPAID,
                ]);
                $remainingAmount -= $outstandingAmount;
            } else {
                
                $scheduledRepayment->update([
                    'outstanding_amount' => $outstandingAmount - $remainingAmount,
                    'status' => ScheduledRepayment::STATUS_PARTIAL,
                ]);
                $remainingAmount = 0;
            }
        }

        
        $loan->refresh(); 
        $totalOutstanding = $loan->scheduledRepayments()->sum('outstanding_amount');
        $status = $totalOutstanding > 0 ? Loan::STATUS_DUE : Loan::STATUS_REPAID;
        
        $loan->update([
            'outstanding_amount' => $totalOutstanding,
            'status' => $status,
        ]);

        return $loan->fresh(['scheduledRepayments']);
    }
}
