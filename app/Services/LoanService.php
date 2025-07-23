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

            $baseRepaymentAmount = intdiv($amount, $terms);
            $remainder = $amount % $terms;

            for ($i = 1; $i <= $terms; $i++) {
                $repaymentAmount = $baseRepaymentAmount;
                if ($i === $terms) {
                    $repaymentAmount += $remainder; // Add remainder to the last repayment
                }

                $dueDate = Carbon::parse($processedAt)->addMonths($i);

                $loan->scheduledRepayments()->create([
                    'amount' => $repaymentAmount,
                    'outstanding_amount' => $repaymentAmount,
                    'currency_code' => $currencyCode,
                    'due_date' => $dueDate->toDateString(),
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan;
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
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        return DB::transaction(function () use ($loan, $receivedRepayment, $currencyCode, $receivedAt) {

            $loan->receivedRepayments()->create([
                'amount' => $receivedRepayment,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            $loan->outstanding_amount -= $receivedRepayment;

            $repaymentsToSettle = $loan->scheduledRepayments()
                ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
                ->get();

            $paymentLeft = $receivedRepayment;

            foreach ($repaymentsToSettle as $repayment) {
                if ($paymentLeft <= 0) break;

                $amountToPay = min($paymentLeft, $repayment->outstanding_amount);
                
                $repayment->outstanding_amount -= $amountToPay;
                $paymentLeft -= $amountToPay;

                if ($repayment->outstanding_amount == 0) {
                    $repayment->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $repayment->status = ScheduledRepayment::STATUS_PARTIAL;
                }
                $repayment->save();
            }

            if ($loan->outstanding_amount <= 0) {
                $loan->status = Loan::STATUS_REPAID;
            }
            $loan->save();

            return $loan->fresh(); 
        });
    }
}
