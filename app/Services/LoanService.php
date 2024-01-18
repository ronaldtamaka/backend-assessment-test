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

        $amountValue = (int)($amount / $terms);
        $amountValueRound = round($amount / $terms);
        $divAmount = $amount - ($amountValue * 3 );

        $dueDate = Carbon::parse($processedAt);
        for ($i = 1; $i <= $terms; $i++) {
            $getAmount = $amountValue;
            if ( $amountValue != $amountValueRound && $i + $divAmount > $terms) 
                $getAmount = $amountValueRound;

            $dueDate = $dueDate->addMonths(1);

            $scheduledRepayment = ([
                'loan_id' => $loan->id,
                'amount' => $getAmount,
                'outstanding_amount' => $getAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE
            ]);
            ScheduledRepayment::create($scheduledRepayment);

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
        $received = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'received_at' => $receivedAt,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
        ]);
       
        $loan->update(['outstanding_amount' => $loan->outstanding_amount - $amount]);
        $schedule = ScheduledRepayment::where('due_date', $receivedAt)->first();
        if($schedule->outstanding_amount < $amount){
            $amount_schedule = $schedule->outstanding_amount;

            $schedule->update(['outstanding_amount' => 0, 'status' => ScheduledRepayment::STATUS_REPAID]);
            $schedules = ScheduledRepayment::where('due_date','>' ,$receivedAt)->get();
            if(count($schedules) > 0){
                $temp = $amount -  $amount_schedule;
                foreach($schedules as $s)
                {
                    $cek = $s->outstanding_amount - $temp;
                    if($cek > 0){
                        $s->update(['outstanding_amount' => $cek, 'status' => ScheduledRepayment::STATUS_PARTIAL]);
                        break;
                    }elseif($cek < 0){
                        $s->update(['outstanding_amount' => $temp, 'status' => ScheduledRepayment::STATUS_REPAID]);
                        $temp = $temp - $s->outstanding_amount;

                    }else{
                        $s->update(['outstanding_amount' => 0, 'status' => ScheduledRepayment::STATUS_REPAID]);
                        break;
                    }
                }
            }
            
            



        }else{
            $schedule->update(['outstanding_amount' => 0, 'status' => ScheduledRepayment::STATUS_REPAID]);
        }
        if($loan->outstanding_amount <= 0)
        {
            $loan->update(['status' => ScheduledRepayment::STATUS_REPAID]);
        }
        return $received;
    }
}
