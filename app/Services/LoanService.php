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
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $due = Carbon::parse($processedAt)->addMonths(1);
        for ($i = 1; $i <= $terms; $i++) {
            $termsAmount = ($i==$terms) ? ceil($amount/$terms) : floor($amount/$terms);
            $scheduledRepayment = ([
                'loan_id' => $loan->id,
                'amount' => $termsAmount,
                'outstanding_amount' => $termsAmount,
                'currency_code' => $currencyCode,
                'due_date' => $due->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE
            ]);
            ScheduledRepayment::create($scheduledRepayment);

            $due = $due->addMonths(1);
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
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id'=>$loan->id,
            'amount'=>$amount,
            'currency_code'=>$currencyCode,
            'received_at'=>$receivedAt,
        ]);

        $status = Loan::STATUS_DUE;
        $outstanding_amount=$loan->amount;
        $outstanding_amount=$loan->amount-$loan->scheduledRepayments()->repaid()->sum('amount')-$amount;

        $scheduledRepayment = $loan->scheduledRepayments()->due()->get();
        foreach($scheduledRepayment as $sr){
            if ($amount>0){
                //$loan->amount-=$sr->amount;
                if ($sr->amount > $amount){
                    $updateSr = [
                        'outstanding_amount'=> $sr->amount - $amount,
                        'status'=>ScheduledRepayment::STATUS_PARTIAL
                    ];
                }else{
                    $updateSr = [
                        'outstanding_amount'=> 0,
                        'status'=>ScheduledRepayment::STATUS_REPAID
                    ];
                }
                $amount -= $sr->amount;
            }else{
                $updateSr = [
                    'outstanding_amount'=> $sr->amount,
                ];
            }

            //ScheduledRepayment::where("loan_id",$sr->loan_id)->where("amount",$sr->amount)->where("due_date",$sr->due_date)->update($updateSr);
            $sr->update($updateSr);
        }

        if ($loan->scheduledRepayments()->repaid()->count()==$loan->terms){
            $status = Loan::STATUS_REPAID;
            $outstanding_amount=0;
        }

        $loan->update(['outstanding_amount'=>$outstanding_amount, 'status'=>$status]);


        return $receivedRepayment;
    }
}
