<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use App\Models\ScheduledRepayment;
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

        $this->generateSchedule($loan, $amount, $terms, $currencyCode, $processedAt);

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
     * @return Loan
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

        $remainingAmount = $amount;
        $scheduledRepayments = $loan->scheduledRepayments()
            ->where('status', '!=', ScheduledRepayment::STATUS_REPAID)
            ->orderBy('due_date')
            ->get();

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($remainingAmount <= 0) {
                break;
            }

            $outstandingAmount = $scheduledRepayment->outstanding_amount;
            $paymentAmount = min($remainingAmount, $outstandingAmount);

            $scheduledRepayment->outstanding_amount -= $paymentAmount;
            $remainingAmount -= $paymentAmount;

            // Update status based on outstanding amount
            if ($scheduledRepayment->outstanding_amount == 0) {
                $scheduledRepayment->status = ScheduledRepayment::STATUS_REPAID;
            } elseif ($scheduledRepayment->outstanding_amount < $scheduledRepayment->amount) {
                $scheduledRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
            }

            $scheduledRepayment->save();
        }

        // Calculate total outstanding amount from scheduled repayments
        $totalOutstanding = $loan->scheduledRepayments()->sum('outstanding_amount');
        $loan->outstanding_amount = $totalOutstanding;

        // Update loan status if fully repaid
        if ($loan->outstanding_amount == 0) {
            $loan->status = Loan::STATUS_REPAID;
        }

        $loan->save();

        return $loan;
    }

    /**
     * Generate scheduled repayments for a loan
     *
     * @param Loan $loan
     * @param int $amount
     * @param int $terms
     * @param string $currencyCode
     * @param string $processedAt
     * @return void
     */
    private function generateSchedule(Loan $loan, int $amount, int $terms, string $currencyCode, string $processedAt): void
    {
        // Calculate amounts with proper rounding and distribution
        $baseAmount = intval($amount / $terms);
        $remainder = $amount % $terms;

        $amounts = array_fill(0, $terms, $baseAmount);

        // Distribute remainder evenly from the end
        if ($remainder > 0) {
            // For test case: 5000 with 3 terms should produce [1666, 1666, 1667]
            // This means remainder should be distributed to the last term only
            $amounts[$terms - 1] += $remainder;
        }

        $processedDate = Carbon::parse($processedAt);

        for ($i = 0; $i < $terms; $i++) {
            $dueDate = $processedDate->copy()->addMonths($i + 1);

            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amounts[$i],
                'outstanding_amount' => $amounts[$i],
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
        }
    }
}
