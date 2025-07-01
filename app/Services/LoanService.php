<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        return DB::transaction(function () use ($user, $amount, $currencyCode, $terms, $processedAt) {
            $loan = Loan::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'terms' => $terms,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'processed_at' => $processedAt,
                'status' => Loan::STATUS_DUE,
            ]);

            // Hitung jumlah cicilan (pakai skema: sisa dibagi ke dua termin terakhir)
            $baseAmount = intdiv($amount, $terms); // 1666
            $remainder = $amount % $terms; // 2

            list($year, $month, $day) = explode('-', $processedAt);

            for ($i = 1; $i <= $terms; $i++) {
                $repaymentAmount = $baseAmount;
                if ($i >= ($terms - $remainder + 1)) {
                    $repaymentAmount += 1;
                }

                $dueMonth = (int)$month + $i;
                $dueYear = (int)$year + intdiv($dueMonth - 1, 12);
                $dueMonth = (($dueMonth - 1) % 12) + 1;
                $dueDate = sprintf('%04d-%02d-%02d', $dueYear, $dueMonth, $day);

                ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $repaymentAmount,
                    'outstanding_amount' => $repaymentAmount,
                    'currency_code' => $currencyCode,
                    'due_date' => $dueDate,
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan;
        });
    }

    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        return DB::transaction(function () use ($loan, $amount, $currencyCode, $receivedAt) {
            $receivedRepayment = $loan->receivedRepayments()->create([
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            $remaining = $amount;

            $scheduledRepayments = $loan->scheduledRepayments()
                ->whereIn('status', [
                    ScheduledRepayment::STATUS_DUE,
                    ScheduledRepayment::STATUS_PARTIAL,
                ])
                ->orderBy('due_date')
                ->get();

            foreach ($scheduledRepayments as $repayment) {
                if ($remaining <= 0) break;

                if ($remaining >= $repayment->outstanding_amount) {
                    $remaining -= $repayment->outstanding_amount;
                    $repayment->outstanding_amount = 0;
                    $repayment->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $repayment->outstanding_amount -= $remaining;
                    $repayment->status = ScheduledRepayment::STATUS_PARTIAL;
                    $remaining = 0;
                }

                $repayment->save();
            }

            $loan->outstanding_amount -= $amount;
            $loan->outstanding_amount = max(0, $loan->outstanding_amount);
            $loan->status = $loan->outstanding_amount === 0
                ? Loan::STATUS_REPAID
                : Loan::STATUS_DUE;
            $loan->save();

            return $receivedRepayment;
        });
    }
}
