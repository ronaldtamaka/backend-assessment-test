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

        // update scheduled repayments for the loan base on received at
        $processScheduleRepayment = $loan->scheduledRepayments()
            ->where(function($query){
                $query->where('status', ScheduledRepayment::STATUS_DUE)
                    ->orWhere('status', ScheduledRepayment::STATUS_PARTIAL);
            })
            ->where('due_date', '<=', $receivedAt)
            ->orderBy('due_date', 'desc')
            ->first();

        // check amount is enough to pay for the last schedule repayment
        if ($amount >= $processScheduleRepayment->amount) {
            $processScheduleRepayment->status = ScheduledRepayment::STATUS_REPAID;
            $processScheduleRepayment->outstanding_amount = 0;
            $processScheduleRepayment->save();

            $amount -= $processScheduleRepayment->outstanding_amount;
        } else {
            $processScheduleRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
            $processScheduleRepayment->outstanding_amount -= $amount;
            $processScheduleRepayment->save();
            $amount = 0;
        }
        
        // check if shcedule is last or not
        $lastScheduleRepayments = $loan->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_DUE)->orderBy('due_date', 'desc')->first();
        if ($receivedAt != $lastScheduleRepayments->due_date) {
            $loan->outstanding_amount = $loan->amount - $amount;
            $loan->status = Loan::STATUS_DUE;
        } else {
            $loan->outstanding_amount = 0;
            $loan->status = Loan::STATUS_REPAID;
        }
        $loan->save();


        return $loan;
       
    }
}
