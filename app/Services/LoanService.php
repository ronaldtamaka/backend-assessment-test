<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\ScheduledRepayment;
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

        $divPaymentAmount = (int) floor($loan->amount / $loan->terms);
        $diffAmount = $loan->amount - ($divPaymentAmount * $terms);

        $arraySchedulePayments = [];
        
        for ($i=1; $i <= $terms; $i++) {
            $dueDate = Carbon::parse($loan->processed_at)->addMonths($i)->format('Y-m-d');

            $arraySchedulePayments[$i] = [
                'amount' => $divPaymentAmount,
                'outstanding_amount' => $divPaymentAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }

        while ($diffAmount > 0) {
            foreach (collect($arraySchedulePayments)->sortKeysDesc() as $key => $value) {
                if ($diffAmount > 0) {
                    $arraySchedulePayments[$key]['amount'] += 1;
                    $arraySchedulePayments[$key]['outstanding_amount'] += 1;
                    $diffAmount -= 1;
                }
            }
        }

        foreach ($arraySchedulePayments as $arraySchedulePayment) {
            $loan->scheduledRepayments()->create($arraySchedulePayment);
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
     * @return Loan
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        $receivePayment = $loan->receivedPayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt
        ]);

        $scheduledRepayments = $loan->scheduledRepayments()
            ->orderBy('due_date', 'asc')
            ->get();

        $outstandingAmount = 0;
        $partialAmount = 0;

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($scheduledRepayment->status == ScheduledRepayment::STATUS_DUE) {

                if ($scheduledRepayment->due_date == $receivedAt) {
                    $scheduledRepayment->update([
                        'status' => ScheduledRepayment::STATUS_REPAID
                    ]);
    
                    $partialAmount = $amount - $scheduledRepayment->amount;
                } else {
                    if ($partialAmount > 0) {
                        $scheduledRepayment->update([
                            'outstanding_amount' => $amount - $scheduledRepayment->amount,
                            'status' => ScheduledRepayment::STATUS_PARTIAL
                        ]);
                    }
                }
                
            }

            if ($scheduledRepayment->status == ScheduledRepayment::STATUS_REPAID) {
                $scheduledRepayment->update([
                    'outstanding_amount' => 0,
                ]);
                $outstandingAmount += $scheduledRepayment->amount;
            }
        }
        
        $outstandingAmount = $loan->outstanding_amount - $outstandingAmount - $partialAmount;
        $loan->update([
            'outstanding_amount' => $outstandingAmount,
            'status' => $outstandingAmount > 0 ? Loan::STATUS_DUE : Loan::STATUS_REPAID
        ]);

        return $loan; 
    }
}
