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


        $loan = Loan::create(
            [
                'user_id' => $user->id,
                'amount' => $amount,
                'terms' => $terms,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'processed_at' => $processedAt,
                'status' => Loan::STATUS_DUE
            ]
        );

        $processedDate = Carbon::parse($processedAt);

        $rawRepaymentAmount = $amount / $terms;
        $repaymentAmount = floor($rawRepaymentAmount);
        $fraction = 0;

        for ($i=1; $i <= $terms; $i++) {

            $dueDate = $processedDate->addMonth()->format("Y-m-d");

            if ($i === $terms) {
                $fraction += $rawRepaymentAmount - $repaymentAmount;
                $repaymentAmount = ceil($fraction + ($amount / $terms));
            }

            $scheduledRepayment = new ScheduledRepayment(
                [
                    'amount' => $repaymentAmount,
                    'outstanding_amount' => $repaymentAmount,
                    'currency_code' => $currencyCode,
                    'due_date' => $dueDate,
                    'status' => ScheduledRepayment::STATUS_DUE
                ]
            );

            $loan->scheduledRepayments()->save($scheduledRepayment);
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
        $currentScheduledPayment = $loan->scheduledRepayments()
            ->whereDate('due_date', '=', $receivedAt)
            ->first();

        foreach($loan->scheduledRepayments as $scheduledPayment) {
            if($scheduledPayment->id != $currentScheduledPayment->id) {
                $scheduledPayment->update([
                    'status' => ($scheduledPayment->status == '') ? ScheduledRepayment::STATUS_DUE : $scheduledPayment->status,
                ]);
            }else{
                $outStandingAmount = $currentScheduledPayment->outstanding_amount;
                $status = ScheduledRepayment::STATUS_REPAID;

                if ($amount > $scheduledPayment->amount)
                {
                     $nextId = $loan->scheduledRepayments->where('id' , '>', $currentScheduledPayment->id)->min('id');

                     $scheduledPayment->update([
                        'status' => ScheduledRepayment::STATUS_REPAID,
                        'outstanding_amount' => $outStandingAmount < 0 ? abs($outStandingAmount) : 0
                    ]);

                    $nextScheduledPayment = $loan->scheduledRepayments->where('id', $nextId)->first();

                    $nextScheduledPayment->update([
                        'status' => ScheduledRepayment::STATUS_PARTIAL,
                        'outstanding_amount' => $amount - $nextScheduledPayment->amount
                    ]);

                }else{
                     $scheduledPayment->update([
                        'status' => ScheduledRepayment::STATUS_REPAID,
                        'outstanding_amount' => $outStandingAmount < 0 ? abs($outStandingAmount) : 0
                    ]);
                }

            }

        }

        $receivedRepayment = $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $outStandingAmount = $loan->amount - $amount;

        $countRepaid = $loan->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_REPAID)->count();

        if ($countRepaid == $loan->terms){
            $loan->update([
                'outstanding_amount' => 0,
                'status' => Loan::STATUS_REPAID
            ]);

        }else{
            $loan->update([
                'outstanding_amount' => $outStandingAmount,
                'status' => Loan::STATUS_DUE
            ]);
        }




        return $receivedRepayment;

    }
}
