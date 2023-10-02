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

        $monthlyAmount = round($amount / $terms);
        $dueDate = Carbon::parse($loan->processed_at);

        for ($i = 1; $i <= $terms; $i++) {
            $dueDate = $dueDate->addMonth();
            $inputAmount = ($i === $terms) ? $loan->outstanding_amount : $monthlyAmount;

            $loan->scheduledRepayments()->create([
                'amount' => $inputAmount,
                'outstanding_amount' => $inputAmount,
                'currency_code' => $loan->currency_code,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);

            if ($i !== $terms) {
                $loan->outstanding_amount -= $monthlyAmount;
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
        $receivedRepayment = $this->receivePayment($loan, $amount, $currencyCode, $receivedAt);
        $this->scheduleRepayments($loan, $receivedRepayment);
        $this->updateLoanStatus($loan, $receivedRepayment);

        return $receivedRepayment;
    }

    private function receivePayment(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        return $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);
    }

    private function scheduleRepayments(Loan $loan, int $receivedRepayment): void
    {
        $scheduleRepayments = $loan->scheduledRepayments()
            ->whereDate('due_date', '>=', $loan->received_at)
            ->orderBy('due_date')
            ->get();

        foreach ($scheduleRepayments as $schedule) {
            if ($receivedRepayment <= 0) {
                break;
            }

            if ($schedule->amount <= $receivedRepayment) {
                $receivedRepayment -= $schedule->amount;
                $schedule->update([
                    'outstanding_amount' => 0,
                    'status' => ScheduledRepayment::STATUS_REPAID,
                ]);
            } else {
                $schedule->update([
                    'outstanding_amount' => $schedule->amount - $receivedRepayment,
                    'status' => ScheduledRepayment::STATUS_PARTIAL,
                ]);
                $receivedRepayment = 0;
            }
        }
    }

    private function updateLoanStatus(Loan $loan, int $receivedRepayment): void
    {
        if ($loan->outstanding_amount - $receivedRepayment <= 0) {
            $loan->update([
                'outstanding_amount' => 0,
                'status' => Loan::STATUS_REPAID,
            ]);
        } else {
            $loan->update([
                'outstanding_amount' => $loan->outstanding_amount - $receivedRepayment,
            ]);
        }
    }
}
