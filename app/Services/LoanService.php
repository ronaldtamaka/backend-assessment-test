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
        // store received payment
        $received = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $currencyAmount = $this->parseAmountToLoanCurrency($amount, $currencyCode, $loan->currency_code);

        // get total repaid amount
        $repaidAmount = $loan
            ->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_REPAID)
            ->sum('amount');

        // update loan outstanding and status
        $loan
            ->forceFill([
                'outstanding_amount' => $loanOutstanding = $loan->outstanding_amount - ($repaidAmount + $currencyAmount),
                'status' => $loanOutstanding === 0 ? Loan::STATUS_REPAID : Loan::STATUS_DUE,
            ])
            ->save();

        // get unpaid schedule repayment
        $unpaidRepayments = $loan
            ->scheduledRepayments()
            ->where('status', '<>', ScheduledRepayment::STATUS_REPAID)
            ->get();

        foreach ($unpaidRepayments as $unpaidRepayment) {
            // skip the process if amount is 0
            if ($currencyAmount === 0) {
                continue;
            }

            $repayment = min($unpaidRepayment->amount, $unpaidRepayment->outstanding_amount, $currencyAmount);
            $currencyAmount -= $repayment;

            // update schedule repayment
            $unpaidRepayment
                ->forceFill([
                    'outstanding_amount' => $repaymentOutstanding = $unpaidRepayment->outstanding_amount - $repayment,
                    'status' => $repaymentOutstanding === 0 ? ScheduledRepayment::STATUS_REPAID : ScheduledRepayment::STATUS_PARTIAL,
                ])
                ->save();
        }

        return $received;
    }

    /**
     * Parse amount to loan currency
     *
     * @param  int  $amount
     * @param  string  $from
     * @param  string  $to
     * @return int
     */
    protected function parseAmountToLoanCurrency(int $amount, string $from, string $to): int
    {
        if ($from === $to) {
            return $amount;
        }

        // assume the rate is 1
        $rate = 1;

        return $amount * $rate;
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
