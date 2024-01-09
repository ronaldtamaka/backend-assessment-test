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
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $pay = floor($amount / $terms);
        $dueDate = Carbon::parse($processedAt);

        for ($i = 1; $i <= $terms; $i++) {
            if ($i === $terms) {
                $pay = $amount - ($pay * ($terms - 1))-1;
            }

            $loan->scheduledRepayments()->create([
                'amount' => $pay,
                'outstanding_amount' => $pay,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->addMonth()->format('Y-m-d'),
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
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        $paymentReceived = $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $scheduledRepayments = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_DUE)
            ->orderBy('due_date')
            ->get();

        $paymentLeft = $amount;
        foreach ($scheduledRepayments as $scheduledRepayment) {
            $paymentLeft -= $scheduledRepayment->amount;
            dump($scheduledRepayment->amount);
            if ($paymentLeft > 0) {
                $scheduledRepayment->update([
                    'outstanding_amount' => 0,
                    'currency_code' => $currencyCode,
                    'status' => ScheduledRepayment::STATUS_REPAID
                ]);
            } else {
                $scheduledRepayment->update([
                    'outstanding_amount' => $paymentLeft,
                    'currency_code' => $currencyCode,
                    'status' => $paymentLeft <= 0 ? ScheduledRepayment::STATUS_REPAID : ScheduledRepayment::STATUS_PARTIAL
                ]);
                break;
            }
            
        }

        $paidAmount = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_REPAID)
            ->sum('amount');
        
        $loan->outstanding_amount = $loan->amount - $paidAmount;
        $loan->save();

        return $paymentReceived;

    }
}
