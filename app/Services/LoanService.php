<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use App\Models\ReceivedRepayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    
    public function createLoan($user, float $amount, string $currencyCode, int $terms, string $processedAt): Loan
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

            $installmentAmount = $amount / $terms;
            $date = Carbon::parse($processedAt);

            for ($i = 1; $i <= $terms; $i++) {
                ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $installmentAmount,
                    'outstanding_amount' => $installmentAmount,
                    'currency_code' => $currencyCode,
                    'due_date' => $date->copy()->addMonths($i)->format('Y-m-d'),
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan->refresh();
        });
    }

    /**
     * Repay a loan and update related schedules.
     */
    public function repayLoan(Loan $loan, float $amount, string $currency, string $date): Loan
    {
        $remaining = $amount;

    
        $repayments = $loan->scheduledRepayments()
            ->where('status', '!=', ScheduledRepayment::STATUS_REPAID)
            ->orderBy('due_date')
            ->get();

        foreach ($repayments as $repayment) {
            if ($remaining <= 0) break;

            $pay = min($repayment->outstanding_amount, $remaining);
            $repayment->outstanding_amount -= $pay;

            if ($repayment->outstanding_amount <= 0) {
                $repayment->status = ScheduledRepayment::STATUS_REPAID;
                $repayment->outstanding_amount = 0;
            } else {
                $repayment->status = ScheduledRepayment::STATUS_PARTIAL;
            }
            $repayment->save();

            $remaining -= $pay;
        }

    
        $loan->outstanding_amount = $loan->scheduledRepayments()
            ->where('status', '!=', ScheduledRepayment::STATUS_REPAID)
            ->sum('outstanding_amount');

        $loan->status = $loan->outstanding_amount <= 0
            ? Loan::STATUS_REPAID
            : Loan::STATUS_DUE;

        $loan->save();

        $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currency,
            'received_at' => $date,
        ]);

        return $loan;
    }
}
