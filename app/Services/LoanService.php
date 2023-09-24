<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;

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
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
            'currency_code' => $currencyCode,
        ]);
        $remainder = $amount % $terms;
        $amount_terms = floor($amount/$terms);
        for ($x = 1; $x <= $terms; $x++) {
            $time = strtotime($processedAt);
            $final_time = date("Y-m-d", strtotime($x." month", $time));
            if($x == $terms){
                $schedule = ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $amount_terms + $remainder,
                    'due_date' => $final_time,
                    'outstanding_amount' => $amount_terms + $remainder,
                    'processed_at' => $processedAt,
                    'status' => ScheduledRepayment::STATUS_DUE,
                    'currency_code' => $currencyCode,
                ]);

            }else{
                $schedule = ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $amount_terms,
                    'due_date' => $final_time,
                    'outstanding_amount' => $amount_terms,
                    'processed_at' => $processedAt,
                    'status' => ScheduledRepayment::STATUS_DUE,
                    'currency_code' => $currencyCode,
                ]);
            }
            
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
        $schedule = ReceivedRepayment::create([
            'loan_id' => $user->id,
            'amount' => $amount,
            'due_date' => $final_time,
            'outstanding_amount' => $amount_terms,
            'processed_at' => $processedAt,
            'currency_code' => $currencyCode,
        ]);
    }
}
