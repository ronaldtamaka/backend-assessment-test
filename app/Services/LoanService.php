<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanService
{
    /**
     * Special Note from me
     * im really sorry if i was wrong, i just wanna share the confusion i had, cause i can't make a changes in Unit Test this time
     * 
     * 1. I don't know why in the test (testServiceCanCreateLoanOfForACustomer)
     * the amount of scheduled repayments should be 1666 + 1666 + 1667, cause it would be 4999 instead of 5000
     * 
     * 2. In the test testServiceCanRepayAScheduledRepaymentConsecutively at section (Asserting Last Scheduled Repayment is Repaid)
     * you expect the 3rd scheduled_repayments due_date was 2020-02-20, but of course that is should be 2020-04-20
     * 
     * 3. And last in the case testServiceCanRepayMultipleScheduledRepayments at section Asserting First Scheduled Repayment is Repaid
     * you expect the amount of 1st scheduled_repayments to be 1667, but you already defined it as 1666 in the $scheduledRepaymentOne declaration,
     * also in section Asserting Second Scheduled Repayment is Partial you expect the outstanding_amount was 333 (2000 - 1667), but the actual
     * remaining paid amount was 334 (2000 - 1666), so the outstanding_amount should be 1333 (1667 - 334)
     * 
     * I also encountered several issues, like class name mismatches for DebitCardPolicy and DebitCardTransactionPolicy,
     * which are in the Policies folder but you defined as Polocies. There were also errors in the migration constraints.
     * I know I can skip these, as you warned in the assessment rules, but I would really struggle if I had to work on them.
     * 
     * Once again, I’m really sorry if I misunderstood this assessment test, sir.
     */

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
    public function createLoan(
        User $user,
        int $amount,
        string $currencyCode,
        int $terms,
        string $processedAt
    ): Loan {
        return DB::transaction(function () use ($user, $amount, $currencyCode, $terms, $processedAt) {
            if ($amount <= 0 || $terms <= 0) {
                throw new InvalidArgumentException('Invalid loan params');
            }

            $loan = Loan::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'terms' => $terms,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'processed_at' => $processedAt,
                'status' => Loan::STATUS_DUE,
            ]);

            $baseInstallment = intdiv($amount, $terms);
            $remainder = $amount % $terms;

            for ($i = 1; $i <= $terms; $i++) {
                $thisInstallment = $baseInstallment;

                // Add 1 to the last `remainder` installments
                if ($i > $terms - $remainder) {
                    $thisInstallment += 1;
                }

                ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $thisInstallment,
                    'outstanding_amount' => $thisInstallment,
                    'currency_code' => strtoupper($currencyCode),
                    'due_date' => Carbon::parse($processedAt)->addMonths($i)->toDateString(),
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan;
        });
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
    public function repayLoan(
        Loan $loan,
        int $amount,
        string $currencyCode,
        string $receivedAt
    ): Loan {
        return DB::transaction(function () use ($loan, $amount, $currencyCode, $receivedAt) {
            if ($amount <= 0) {
                throw new InvalidArgumentException('Repayment must be more than 0');
            }

            if ($loan->currency_code !== strtoupper($currencyCode)) {
                throw new InvalidArgumentException('Currency mismatch');
            }

            ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            // Get scheduled repayments that are not yet fully repaid, ordered by due date.
            $scheduledRepayments = $loan
                ->scheduledRepayments()
                ->whereNotIn('status', [ScheduledRepayment::STATUS_REPAID])
                ->orderBy('due_date')
                ->get();

            $paidAmount = $amount;

            foreach ($scheduledRepayments as $scheduledRepayment) {
                if ($paidAmount <= 0) {
                    $scheduledRepayment->outstanding_amount = $scheduledRepayment->amount;
                    $scheduledRepayment->save();

                    continue;
                }

                $schedulePaymentAmount = $scheduledRepayment->amount;
                $schedulePaymentAmount -= $paidAmount;
                $paidAmount -= $scheduledRepayment->amount;

                if ($schedulePaymentAmount <= 0) $scheduledRepayment->outstanding_amount = 0;
                else {
                    $scheduledRepayment->outstanding_amount = $schedulePaymentAmount;
                }

                if ($scheduledRepayment->outstanding_amount <= 0) {
                    $scheduledRepayment->outstanding_amount = 0;
                    $scheduledRepayment->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $scheduledRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
                }

                $scheduledRepayment->save();
            }

            $loanOutstandingAmount = $loan
                ->scheduledRepayments()
                ->sum('outstanding_amount');

            $loan->outstanding_amount = $loanOutstandingAmount;

            if ($loan->outstanding_amount <= 0) {
                $loan->status = Loan::STATUS_REPAID;
            } else {
                $loan->status = Loan::STATUS_DUE;
            }

            $loan->save();

            return $loan;
        });
    }
}
