<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;
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
        DB::beginTransaction();
        try {

            $loan = $user->loans()->create([
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'terms' => $terms,
                'outstanding_amount' => $amount,
                'status' => Loan::STATUS_DUE,
                'processed_at' => $processedAt
            ]);

            $baseAmount = (int) ceil($amount / $terms);
            $scheduledAmounts = array_fill(0, $terms, $baseAmount);

            $scheduledAmounts[] = (int) floor($amount / $terms);
            $processedDate = Carbon::parse($processedAt);

            // foreach ($scheduledAmounts as $i => $repaymentAmount) {
            //     ScheduledRepayment::create([
            //         'loan_id' => $loan->id,
            //         'amount' => $repaymentAmount,
            //         'outstanding_amount' => $repaymentAmount,
            //         'currency_code' => $currencyCode,
            //         'due_date' => $processedDate->copy()->addMonths($i + 1)->toDateString(),
            //         'status' => ScheduledRepayment::STATUS_DUE,
            //     ]);
            // }
            $scheduledRepayments = [];
            foreach ($scheduledAmounts as $i => $repaymentAmount) {
                $scheduledRepayments[] = [
                    'loan_id' => $loan->id,
                    'amount' => $repaymentAmount,
                    'outstanding_amount' => $repaymentAmount,
                    'currency_code' => $currencyCode,
                    'due_date' => $processedDate->copy()->addMonths($i + 1)->toDateString(),
                    'status' => ScheduledRepayment::STATUS_DUE,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            ScheduledRepayment::insert($scheduledRepayments);
            DB::commit();
            return $loan->fresh('scheduledRepayments');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        DB::beginTransaction();

        try {
            // Create received repayment record
            ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt
            ]);

            $remainingPayment = $amount;

            // Get scheduled repayments ordered by due date
            $scheduledRepayments = $loan->scheduledRepayments()
                ->where('outstanding_amount', '>', 0)
                ->where('status', '!=', ScheduledRepayment::STATUS_REPAID)
                ->orderBy('due_date')
                ->get();

            foreach ($scheduledRepayments as $repayment) {
                if ($remainingPayment <= 0) break;

                $repaymentOutstanding = (int) $repayment->outstanding_amount;

                if ($remainingPayment >= $repaymentOutstanding) {
                    // Payment covers this scheduled repayment completely
                    $repayment->update([
                        'outstanding_amount' => 0,
                        'status' => ScheduledRepayment::STATUS_REPAID
                    ]);

                    $remainingPayment -= $repaymentOutstanding;
                } else {
                    // Partial payment
                    $repayment->update([
                        'outstanding_amount' => $repaymentOutstanding - $remainingPayment,
                        'status' => ScheduledRepayment::STATUS_PARTIAL
                    ]);

                    $remainingPayment = 0;
                }

            }

            // Update loan outstanding amount and status
            $loan->outstanding_amount = (int) $loan->scheduledRepayments()
                ->where('status', '!=', ScheduledRepayment::STATUS_REPAID)
                ->sum('outstanding_amount');

            $loan->status = $loan->outstanding_amount > 0
                ? Loan::STATUS_DUE
                : Loan::STATUS_REPAID;

            $loan->save();

            DB::commit();

            return $loan->fresh('scheduledRepayments');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
