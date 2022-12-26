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
        // create loan
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        //create scheduled repayment
        $processedAt = Carbon::parse($processedAt);
        $outstandingAmount = round($amount / $terms,0,PHP_ROUND_HALF_DOWN);
        $scheduledRepayments = array();
        $sumAmount = 0;
        for ($i = 1; $i <= $terms; $i++) {
            $sumAmount += $outstandingAmount;
            $scheduledRepayments = ([
                'loan_id' => $loan->id,
                'amount' => ($i == $terms)?($amount-$sumAmount)+$outstandingAmount:$outstandingAmount,
                'outstanding_amount' => ($i == $terms)?($amount-$sumAmount)+$outstandingAmount:$outstandingAmount,
                'currency_code' => $currencyCode,
                'due_date' => $processedAt->addMonths($i),
                'status' => ScheduledRepayment::STATUS_DUE
            ]);       
        }
        $loan->scheduledRepayments->createMany([$scheduledRepayments]);
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
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        //update loan outstanding amount
        $loan->outstanding_amount = $loan->amount - $amount;
        if($loan->outstanding_amount == 0){
            $loan->status = Loan::STATUS_REPAID;
        }
        $loan->update();

        //update scheduled repayment status
        $scheduledRepayment = ScheduledRepayment::where([['loan_id'=>$loan->id], ['due_date', $receivedAt]]);
        $scheduledRepaymentAmount = $scheduledRepayment->get(['amount']);
        
        //customer repaid payment
        if($receivedRepayment == $scheduledRepaymentAmount){
            $scheduledRepayment->update(['status'=> ScheduledRepayment::STATUS_REPAID, 'outstanding_amount'=>$scheduledRepaymentAmount-$receivedRepayment]);
        }

        //customer repaid partial payment
        if($receivedRepayment < $scheduledRepaymentAmount){
            $scheduledRepayment->update(['status'=> ScheduledRepayment::STATUS_PARTIAL, 'outstanding_amount'=>$scheduledRepaymentAmount-$receivedRepayment]);
        }

        //customer repay multiple scheduledRepayments
        if($receivedRepayment > $scheduledRepaymentAmount){
            $amountPayment = $receivedRepayment-$scheduledRepaymentAmount;
            $scheduledRepayment->update(['status'=> ScheduledRepayment::STATUS_REPAID, 'outstanding_amount'=> 0]);

            //update next payment outstanding amount
            $nextReceivedAt = Carbon::parse($receivedAt);
            $nextScheduledRepayment = ScheduledRepayment::where([['loan_id'=>$loan->id], ['due_date', $nextReceivedAt->addMonths(1)]]);
            $scheduledRepayment->update(['status'=> ScheduledRepayment::STATUS_PARTIAL, 'outstanding_amount'=> $amountPayment]); 
        }

        return $loan;
    }
}
