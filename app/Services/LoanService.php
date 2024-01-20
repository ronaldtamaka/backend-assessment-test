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
        ]);

        // generate scheduled repayments
        $scheduledRepayments = [];
        $remainsAmount = $loan->amount;
        $monthlyAmount = $amount / $terms;
        $dueDate = $loan->processed_at;
        for($i = 1; $i <= $terms; $i++) {
            $dueDate = Carbon::parse($dueDate)->addMonth()->format('Y-m-d');

            if($i != $terms) {
                $remainsAmount -= $monthlyAmount;
                $amountTerms = round($monthlyAmount);
            } else {
                $amountTerms = round($remainsAmount);
            }

            $scheduledRepayments[] = [
                'amount' => $amountTerms,
                'outstanding_amount' => intval($amountTerms),
                'currency_code' => $loan->currency_code,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }

        $loan->scheduledRepayments()->createMany($scheduledRepayments);

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
        $repaymentAmount = $amount;

        // Received Repayment
        $receivePayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        // Schedule repayment
        $scheduledRepayment = $loan->scheduledRepayments()
            ->whereDate('due_date', '>=', $receivedAt)
            ->orderBy('due_date')
            ->get();

        $scheduledIndex = 0;
        $isLastSchedule = count($scheduledRepayment) == 1 ?? false;
        $isLastScheduleRepaid = false;
        while($repaymentAmount > 0 && $scheduledIndex < count($scheduledRepayment)) {
            $selectedSchedule = $scheduledRepayment[$scheduledIndex];
            if($selectedSchedule->amount - $repaymentAmount <= 0) {
                $repaymentAmount -= $selectedSchedule->amount;
                $selectedSchedule->update([
                    'outstanding_amount' => 0,
                    'status' => ScheduledRepayment::STATUS_REPAID
                ]);
                $isLastScheduleRepaid = $isLastSchedule;
            } else {
                $selectedSchedule->update([
                    'outstanding_amount' => $selectedSchedule->amount - $repaymentAmount,
                    'status' => ScheduledRepayment::STATUS_PARTIAL
                ]);
            }
            $scheduledIndex ++;
        }
//        foreach ($scheduledRepayments as $scheduledRepayment) {
////            $selectedSchedule = $scheduledRepayment;
//            if($scheduledRepayment->amount <= $repaymentAmount) {
//                $repaymentAmount -= $scheduledRepayment->amount;
//                $scheduledRepayment->update([
//                    'outstanding_amount' => 0,
//                    'status' => ScheduledRepayment::STATUS_REPAID
//                ]);
//                $isLastScheduleRepaid = $isLastSchedule;
//            } else {
//                $scheduledRepayment->update([
//                    'outstanding_amount' => $scheduledRepayment->amount - $repaymentAmount,
//                    'status' => ScheduledRepayment::STATUS_PARTIAL
//                ]);
//            }
//            if ($repaymentAmount <= 0) {
//                break;
//            }
//        }

        // Loan
        $loanOutstandingAmount = $loan->outstanding_amount;
        $loanStatus = $loan->status;
        if($loanOutstandingAmount - $amount <= 0) {
            $loanStatus = Loan::STATUS_REPAID;
        }

        if($isLastSchedule && $isLastScheduleRepaid) {
            $loan->update([
                'outstanding_amount' => 0,
                'status' => Loan::STATUS_REPAID,
            ]);
        } else {
            $loan->update([
                'outstanding_amount' => $loanOutstandingAmount - $amount,
                'status' => $loanStatus,
            ]);
        }

        return $receivePayment;
    }
}
