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
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $amountPerTerm = floor($amount / $terms);
        $remaining = $amount % $terms;

        for ($i = 1; $i <= $terms; $i++) {

            $amount;

            if ($remaining > 0 && $i === $terms) {
                $amount = $amountPerTerm + $remaining;
            } else {
                $amount = $amountPerTerm;
            }

            $dueDate = Carbon::parse($processedAt)->addMonths($i)->format('Y-m-d');
            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
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
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        // Create received repayment record
        ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        // Get all due or partial scheduled repayments ordered by due date
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
                // Fully paid
                $scheduledRepayment->update([
                    'outstanding_amount' => 0,
                    'status' => ScheduledRepayment::STATUS_REPAID,
                ]);
                $remainingAmount -= $outstandingAmount;
            } else {
                // Partially paid
                $scheduledRepayment->update([
                    'outstanding_amount' => $outstandingAmount - $remainingAmount,
                    'status' => ScheduledRepayment::STATUS_PARTIAL,
                ]);
                $remainingAmount = 0;
            }
        }

        // Update loan outstanding amount
        $totalOutstanding = $loan->scheduledRepayments()->sum('outstanding_amount');
        $loanStatus = $totalOutstanding == 0 ? Loan::STATUS_REPAID : Loan::STATUS_DUE;

        $loan->update([
            'outstanding_amount' => $totalOutstanding,
            'status' => $loanStatus,
        ]);

        return $loan->fresh(['scheduledRepayments']);
    }
}
