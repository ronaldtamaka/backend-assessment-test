<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use App\Models\ScheduledRepayment;
use Illuminate\Support\Facades\DB;
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
            'outstanding_amount' => $amount,
            'terms' => $terms,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $baseAmount = intdiv($amount, $terms);
        $remainder = $amount % $terms;

        for ($i = 1; $i <= $terms; $i++) {
            $installment = $baseAmount;
            
            // Add 1 to the last $remainder number of installments 
            if ($i > ($terms - $remainder)) {
                $installment += 1;
            }

            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $installment,
                'outstanding_amount' => $installment,
                'currency_code' => $currencyCode,
                'due_date' => Carbon::parse($processedAt)->addMonths($i)->format('Y-m-d'),
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
        $remainingPayment = $amount;
        
        // Process scheduled repayments in order (due_date)
        $scheduledRepayments = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_DUE)
            ->orWhere('status', ScheduledRepayment::STATUS_PARTIAL)
            ->orderBy('due_date')
            ->get();

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($remainingPayment <= 0) {
                break;
            }

            $outstandingAmount = $scheduledRepayment->outstanding_amount;
            $paymentForThis = min($remainingPayment, $outstandingAmount);
            
            $newOutstanding = $outstandingAmount - $paymentForThis;
            $remainingPayment -= $paymentForThis;

            // Determine status
            $status = ScheduledRepayment::STATUS_DUE;
            if ($newOutstanding == 0) {
                $status = ScheduledRepayment::STATUS_REPAID;
            } elseif ($newOutstanding < $scheduledRepayment->amount) {
                $status = ScheduledRepayment::STATUS_PARTIAL;
            }

            $scheduledRepayment->update([
                'outstanding_amount' => $newOutstanding,
                'status' => $status,
            ]);
        }

        // Update loan
        $newLoanOutstanding = $loan->outstanding_amount - $amount;
        $loan->update([
            'outstanding_amount' => $newLoanOutstanding,
            'status' => ($newLoanOutstanding <= 0) ? Loan::STATUS_REPAID : Loan::STATUS_DUE,
        ]);

        // Store the received repayment
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        return $receivedRepayment;
    }
}