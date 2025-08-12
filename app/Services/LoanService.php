<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use App\Models\ScheduledRepayment;
use Illuminate\Support\Facades\DB;
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

            $baseInstallment = intdiv($loan->amount, $loan->terms);
            $remainder = $loan->amount % $loan->terms;

            for ($i = 0; $i < $loan->terms; $i++) {
                $amount = $baseInstallment + ($i < $remainder ? 1 : 0);

                ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $amount,
                    'outstanding_amount' => $amount,
                    'currency_code' => $loan->currency_code,
                    'due_date' => Carbon::parse($loan->processed_at)->addMonths($i + 1),
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan->fresh();
        });
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
    // app/Services/LoanService.php
    public function repayLoan(Loan $loan, int $receivedRepayment, string $currencyCode, string $receivedAt): Loan
    {
        return DB::transaction(function () use ($loan, $receivedRepayment, $currencyCode, $receivedAt) {
            $loan->receivedRepayments()->create([
                'amount' => $receivedRepayment,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            $paymentLeft = $receivedRepayment;
            $repaymentsToSettle = $loan->scheduledRepayments()
                ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
                ->orderBy('due_date')
                ->get();

            foreach ($repaymentsToSettle as $repayment) {
                if ($paymentLeft <= 0) break;

                $amountToPay = min($paymentLeft, $repayment->outstanding_amount);
                $repayment->outstanding_amount -= $amountToPay;
                $paymentLeft -= $amountToPay;

                $repayment->status = $repayment->outstanding_amount <= 0
                    ? ScheduledRepayment::STATUS_REPAID
                    : ScheduledRepayment::STATUS_PARTIAL;
                $repayment->save();
            }

            $loan->outstanding_amount -= $receivedRepayment;
            if ($loan->outstanding_amount < 0) {
                $loan->outstanding_amount = 0;
            }

            if (
                $loan->outstanding_amount === 0 ||
                $loan->scheduledRepayments()->where('status', '!=', ScheduledRepayment::STATUS_REPAID)->count() === 0
            ) {
                $loan->status = Loan::STATUS_REPAID;
                $loan->outstanding_amount = 0;
            }

            $loan->save();

            return $loan->fresh();
        });
    }
}
