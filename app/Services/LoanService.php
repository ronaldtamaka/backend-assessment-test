<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\User;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;

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

        $remainsAmount = $loan->amount;
        $monthlyAmount = $amount / $terms;
        $dueDate = $loan->processed_at;
        for($i = 1; $i <= $terms; $i++) {
            $dueDate = Carbon::parse($dueDate)->addMonth()->format('Y-m-d');

            if($i != $terms) {
                $remainsAmount -= $monthlyAmount;
                $inputAmount = round($monthlyAmount);
            } else {
                $inputAmount = round($remainsAmount);
            }

            $loan->scheduledRepayments()->create([
                'amount' => $inputAmount,
                'outstanding_amount' => intval($inputAmount),
                'currency_code' => $loan->currency_code,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
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
        $receivedRepayment = $amount;

        // Receive payment
        $receivePayment = $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        // Schedule repayment
        $scheduleRepayment = $loan->scheduledRepayments()
            ->whereDate('due_date', '>=', $receivedAt)
            ->orderBy('due_date')
            ->get();

        $scheduleIndex = 0;
        $isLastSchedule = count($scheduleRepayment) == 1 ?? false;
        $isLastScheduleRepaid = false;
        while($receivedRepayment > 0 && $scheduleIndex < count($scheduleRepayment)) {
            $selectedSchedule = $scheduleRepayment[$scheduleIndex];
            if($selectedSchedule->amount - $receivedRepayment <= 0) {
                $receivedRepayment -= $selectedSchedule->amount;
                $tes = $selectedSchedule->update([
                    'outstanding_amount' => 0,
                    'status' => ScheduledRepayment::STATUS_REPAID
                ]);
                $isLastScheduleRepaid = $isLastSchedule;
            } else {
                // if($scheduleIndex != 0) dd($receivedRepayment);
                $selectedSchedule->update([
                    'outstanding_amount' => $selectedSchedule->amount - $receivedRepayment,
                    'status' => ScheduledRepayment::STATUS_PARTIAL
                ]);
            }
            $scheduleIndex ++;
        }

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
