<?php

namespace App\Services;

use App\Models\{Loan, ReceivedRepayment, ScheduledRepayment, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($user, $amount, $currencyCode, $terms, $processedAt) {
            $loan = Loan::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'terms' => $terms,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'processed_at' => $processedAt,
                'status' => Loan::STATUS_DUE,
            ]);

            $baseAmount = intdiv($amount, $terms);
            $remainder = $amount % $terms;
            $dueDate = Carbon::parse($processedAt);

            for ($i = 1; $i <= $terms; $i++) {
                $installmentAmount = $baseAmount + ($i === $terms ? $remainder : 0);
                $dueDate = $dueDate->copy()->addMonthsNoOverflow(1);

                $loan->scheduledRepayments()->create([
                    'amount' => $installmentAmount,
                    'outstanding_amount' => $installmentAmount,
                    'currency_code' => $currencyCode,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan->fresh('scheduledRepayments');
        });
    }

    /**
     * Repay a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     * @return Loan
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        return DB::transaction(function () use ($loan, $amount, $currencyCode, $receivedAt) {
            ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            $remainingAmount = $amount;
            foreach ($loan->scheduledRepayments()->orderBy('due_date')->get() as $repayment) {
                
                if ($remainingAmount <= 0) break;

                if ($repayment->outstanding_amount <= 0) continue;

                if ($remainingAmount >= $repayment->outstanding_amount) {
                    $remainingAmount -= $repayment->outstanding_amount;
                    $repayment->update([
                        'outstanding_amount' => 0,
                        'status' => ScheduledRepayment::STATUS_REPAID,
                    ]);
                } else {
                    $repayment->update([
                        'outstanding_amount' => $repayment->outstanding_amount - $remainingAmount,
                        'status' => ScheduledRepayment::STATUS_PARTIAL,
                    ]);
                    $remainingAmount = 0;
                }
            }

            $totalOutstanding = $loan->scheduledRepayments()->sum('outstanding_amount');
            $loan->update([
                'outstanding_amount' => $totalOutstanding
            ]);

            return $loan->first();
        });
    }
}