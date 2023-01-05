<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
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
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        // generate scheduled repayments
        $scheduledRepayments = [];

        for ($i = 1; $i <= $terms; $i++) {
            $dueDate = date('Y-m-d', strtotime($processedAt . ' + ' . $i . ' month'));

            $amountTerm = floor($amount / $terms);

            if ($i === $terms) {
                $amountTerm = $amount - ($amountTerm * ($terms - 1));
            }

            $scheduledRepayments[] = [
                'amount' => $amountTerm,
                'outstanding_amount' => $amountTerm,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate,
            ];
        }

        $loan->scheduledRepayments()->createMany($scheduledRepayments);

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
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $scheduledRepayments = $loan->scheduledRepayments()
            ->where('status', Loan::STATUS_DUE)
            ->orderBy('due_date', 'asc')
            ->get();

        $amountLeft = $amount;

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($amountLeft <= 0) {
                break;
            }

            $amountTerm = $scheduledRepayment->outstanding_amount;

            if ($amountLeft >= $amountTerm) {
                $amountLeft -= $amountTerm;

                $scheduledRepayment->update([
                    'status' => ScheduledRepayment::STATUS_REPAID,
                    'outstanding_amount' => 0,
                ]);
            } else {
                $scheduledRepayment->update([
                    'outstanding_amount' => $outstandingAmount = $amountTerm - $amountLeft,
                    'status' => ScheduledRepayment::STATUS_PARTIAL
                ]);

                $amountLeft = 0;
            }
        }

        $outstandingAmount = $loan->scheduledRepayments()->sum(
            DB::raw('outstanding_amount')
        );

        $loan->update([
            'outstanding_amount' => $outstandingAmount,
            'status' => $outstandingAmount ? Loan::STATUS_DUE : Loan::STATUS_REPAID,
        ]);

        return $receivedRepayment;
    }
}
