<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;

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
        // create loan of for a customer
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $dueDate = \Carbon\Carbon::parse($processedAt)->addMonths(1);
        for ($i = 1; $i <= $terms; $i++) {
            $getAmount = ($i != $terms) ? floor($amount / $terms) : ceil($amount / $terms);
            $scheduledRepayment = ([
                'loan_id' => $loan->id,
                'amount' => $getAmount,
                'outstanding_amount' => $getAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE
            ]);
            ScheduledRepayment::create($scheduledRepayment);
            $dueDate = $dueDate->addMonths(1);
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
        // initial outstanding amount for load and scheduled repayment
        if ($loan->outstanding_amount == 0 && $loan->status == Loan::STATUS_DUE) {
            $loan->update(['outstanding_amount' => $loan->amount]);
            $scheduledRepayments = $loan->scheduledRepayments;
            foreach ($scheduledRepayments as $scheduledRepayment) {
                $scheduledRepayment->update([
                    'outstanding_amount' => $scheduledRepayment->amount
                ]);
            }
        }

        // create new received repayment
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        // get scheduled repayment
        $scheduledRepayment = ScheduledRepayment::query()
            ->where('loan_id', $loan->id)
            ->where('due_date', $receivedAt)
            ->first();

        // check if scheduled repayment does not exist
        if ($scheduledRepayment == NULL) {
            return $receivedRepayment;
        }

        // repay a scheduled repayment consecutively
        $lastScheduledRepayment = $loan->scheduledRepayments()->orderBy('id', 'desc')->first();
        if ($scheduledRepayment->due_date == $lastScheduledRepayment->due_date) {
            foreach ($loan->scheduledRepayments as $scheduledRepayment) {
                $scheduledRepayment->update([
                    'status' => ScheduledRepayment::STATUS_REPAID,
                    'outstanding_amount' => 0
                ]);
            }

            $firstScheduledRepayment = $loan->scheduledRepayments()->orderBy('id')->first();
            $lastScheduledRepayment->update([
                'status' => ScheduledRepayment::STATUS_REPAID,
                'outstanding_amount' => 0,
                'due_date' => $firstScheduledRepayment->due_date,
            ]);

            $loan->update([
                'outstanding_amount' => 0,
                'status' => Loan::STATUS_REPAID
            ]);

            return $receivedRepayment;
        }
    }
}
