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
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'processed_at' => date('Y-m-d', strtotime($processedAt)),
            'status' => Loan::STATUS_DUE,
        ]);

        $baseInstallment = intdiv($amount, $terms);
        $remainingAmount = $amount - ($baseInstallment * ($terms - 1));

        for ($i = 1; $i <= $terms; $i++) {
            if ($i === $terms) {
                $installmentAmount = $remainingAmount;
            } else {
                $installmentAmount = $baseInstallment;
            }

            $loan->scheduledRepayments()->create([
                'amount' => $installmentAmount,
                'due_date' => date('Y-m-d', strtotime($processedAt . ' + ' . ($i) . ' months')),
                'status' => Loan::STATUS_DUE,
                'outstanding_amount' => $installmentAmount,
                'currency_code' => $currencyCode
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
        $remainingAmount = $amount;

        DB::transaction(function () use (&$loan, &$remainingAmount, $currencyCode, $receivedAt, $amount) {
            $scheduledRepayments = $loan->scheduledRepayments()
                ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
                ->orderBy('due_date', 'asc')
                ->get();

            foreach ($scheduledRepayments as $scheduledRepayment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $srOutstanding = (int) $scheduledRepayment->outstanding_amount;
                $paymentForThisRepayment = min($remainingAmount, $srOutstanding);

                $scheduledRepayment->outstanding_amount = $srOutstanding - $paymentForThisRepayment;

                if ($scheduledRepayment->outstanding_amount <= 0) {
                    $scheduledRepayment->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $scheduledRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
                }

                $scheduledRepayment->save();

                $remainingAmount -= $paymentForThisRepayment;
            }

            $paidAmount = $amount - $remainingAmount;

            if ($paidAmount > 0) {
                $loan->outstanding_amount = $loan->outstanding_amount - $paidAmount;

                if ($loan->outstanding_amount <= 0) {
                    $loan->status = Loan::STATUS_REPAID;
                    $loan->outstanding_amount = 0;
                }
                
                $loan->save();

                ReceivedRepayment::create([
                    'loan_id'       => $loan->id,
                    'amount'        => $paidAmount,
                    'currency_code' => $currencyCode,
                    'received_at'   => Carbon::parse($receivedAt)->format('Y-m-d'),
                ]);
            }
        });

        $allRepaid = $loan->scheduledRepayments()
            ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
            ->count() === 0;
        if ($allRepaid) {
            $loan->status = Loan::STATUS_REPAID;
            $loan->outstanding_amount = 0;
            $loan->save();
        }

        return $loan->refresh();
    }
}
