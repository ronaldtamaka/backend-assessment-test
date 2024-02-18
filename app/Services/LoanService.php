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
     * @throws \Throwable
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        DB::beginTransaction();
        try {
            /** @var Loan $loan */
            $loan = Loan::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'terms' => $terms,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'processed_at' => $processedAt,
                'status' => Loan::STATUS_DUE,
            ]);

            $loan->scheduledRepayments()->createMany(
                $this->prepareSchedules($loan)
            );

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
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
        //
    }

    /**
     * Prepare data of scheduled repayment.
     *
     * @param  Loan  $loan
     * @return array
     */
    protected function prepareSchedules(Loan $loan): array
    {
        $schedules = [];
        $totalAmount = $loan->amount;
        $amountPerTerm = intdiv($loan->amount, $loan->terms);
        $termStartedAt = $loan->processed_at;

        for ($i = 1; $i <= $loan->terms; $i++) {
            $dueDate = Carbon::parse($termStartedAt)->addMonth()->format('Y-m-d');
            $termStartedAt = $dueDate;

            if ($i === $loan->terms) {
                $amountPerTerm = round($totalAmount);
            }

            $schedules[] = [
                'amount' => $amountPerTerm,
                'outstanding_amount' => $amountPerTerm,
                'currency_code' => $loan->currency_code,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
            ];

            $totalAmount -= $amountPerTerm;
        }

        return $schedules;
    }
}
