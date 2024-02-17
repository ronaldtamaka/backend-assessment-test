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
            'status' => Loan::STATUS_DUE
        ]);

        $param = [
            'amount' => $amount,
            'terms' => $terms,
        ];

        // generate scheduled repayments
        $this->createScheduledRepayments($loan, $param);

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
        //
    }

    /**
     * schedule repayment
     */
    protected function createScheduledRepayments(Loan $loan, array $param)
    {
        $repayments = [];
        $remainsAmount = $loan->amount;
        $monthlyAmount = $param['amount'] / $param['terms'];
        $dueDate = $loan->processed_at;

        for ($i = 1; $i <= $param['terms']; $i++) {
            $dueDate = Carbon::parse($dueDate)->addMonth()->format('Y-m-d');

            if ($i != $param['terms']) {
                $remainsAmount -= $monthlyAmount;
                $amountTerms = round($monthlyAmount);
            } else {
                $amountTerms = round($remainsAmount);
            }

            $repayments[] = [
                'amount' => $amountTerms,
                'outstanding_amount' => intval($amountTerms),
                'currency_code' => $loan->currency_code,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }

        $loan->scheduledRepayments()->createMany($repayments);
    }
}
