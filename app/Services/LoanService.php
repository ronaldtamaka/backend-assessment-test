<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        // Transaction
        DB::beginTransaction();
        // create loan and connect it to the user
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $eachPart = floor($amount / $terms);
        $eachPartModulus = $amount % $terms;

        // create scheduled repayments for the loan
        $monthDue = Carbon::parse($processedAt);
        $scheduledRepayments = [];
        for ($i = 0; $i < $terms; $i++) {
            $outstandingAmount = $eachPart+($eachPartModulus > 0 ? 1 : 0);
            $eachPartModulus--;
            $scheduledRepayments[] = [
                'loan_id' => $loan->id,
                'amount' => $outstandingAmount,
                'outstanding_amount' => $outstandingAmount,
                'currency_code' => $currencyCode,
                'due_date' => $monthDue->addMonth()->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }
        ScheduledRepayment::insert($scheduledRepayments);

        // commit transaction
        DB::commit();

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
     * @return Loan
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        // Transaction
        DB::beginTransaction();


        // create received repayment for the loan
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $amountRepaid = 0;

        // get scheduled repayments repaid
        $scheduledRepaymentsRepaid = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_REPAID)
            ->orderBy('due_date')->get();

        foreach ($scheduledRepaymentsRepaid as $scheduledRepayment) {
            $amountRepaid += $scheduledRepayment->amount;
        }

        // get scheduled repayments due or partial
        $receivedRepaymentAmount = $amount;

        $scheduledRepayments = $loan->scheduledRepayments()
            ->where(function ($query) {
                $query->where('status', ScheduledRepayment::STATUS_DUE)
                    ->orWhere('status', ScheduledRepayment::STATUS_PARTIAL);
            })
            ->orderBy('due_date')->get();
        // dd($scheduledRepayments->toArray());
        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($receivedRepaymentAmount == 0) {
                break;
            }
            if($receivedRepaymentAmount >= $scheduledRepayment->amount){
                $receivedRepaymentAmount -= $scheduledRepayment->amount;
                $scheduledRepayment->status = ScheduledRepayment::STATUS_REPAID;
                $scheduledRepayment->outstanding_amount = 0;
                $scheduledRepayment->save();
                $amountRepaid += $scheduledRepayment->amount - $scheduledRepayment->outstanding_amount;
            }else{
                $scheduledRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
                $scheduledRepayment->outstanding_amount = $scheduledRepayment->amount - $receivedRepaymentAmount;
                $scheduledRepayment->save();
                $amountRepaid += $receivedRepaymentAmount;
                $receivedRepaymentAmount = 0;
            }
        }


        // update loan outstanding amount
        $loan->outstanding_amount = $loan->amount - $amountRepaid;
        // if outstanding amount is 0, loan is repaid
        if ($loan->outstanding_amount == 0) {
            $loan->status = Loan::STATUS_REPAID;
        }
        $loan->save();

        return $loan;
       
    }
}
