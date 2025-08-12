<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use App\Models\ReceivedRepayment;
use App\Models\User;
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

            $baseAmount = intdiv($amount, $terms);
            $remaining = $amount - ($baseAmount * $terms);

            // Distribusi: 1666, 1666, 1668 (jika 5000 dan 3 terms)
            $schedule = [];
            for ($i = 0; $i < $terms; $i++) {
                $schedule[] = $baseAmount + ($i >= $terms - $remaining ? 1 : 0);
            }

            for ($i = 0; $i < $terms; $i++) {
                $dueDate = date('Y-m-d', strtotime("+".($i + 1)." month", strtotime($processedAt)));

                $loan->scheduledRepayments()->create([
                    'amount' => $schedule[$i],
                    'outstanding_amount' => $schedule[$i],
                    'currency_code' => $currencyCode,
                    'due_date' => $dueDate,
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan;
        });
    }

    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        return DB::transaction(function () use ($loan, $amount, $currencyCode, $receivedAt) {
            $remainingAmount = $amount;

            $repayments = $loan->scheduledRepayments()
                ->orderBy('due_date')
                ->lockForUpdate()
                ->get();

            foreach ($repayments as $repayment) {
                // Skip jika sudah lunas
                if ($repayment->outstanding_amount <= 0) {
                    continue;
                }

                $repaymentDue = $repayment->outstanding_amount;

                if ($remainingAmount >= $repaymentDue) {
                    $repayment->outstanding_amount = 0;
                    $repayment->status = ScheduledRepayment::STATUS_REPAID;
                    $remainingAmount -= $repaymentDue;
                } else {
                    $repayment->outstanding_amount -= $remainingAmount;
                    $repayment->status = ScheduledRepayment::STATUS_PARTIAL;
                    $remainingAmount = 0;
                }

                $repayment->save();

                if ($remainingAmount <= 0) {
                    break;
                }
            }

            // Hitung ulang total outstanding dari repayment yang masih due/partial
            $totalOutstanding = ScheduledRepayment::where('loan_id', $loan->id)
                ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
                ->sum('outstanding_amount');

            $loan->outstanding_amount = $totalOutstanding;
            $loan->status = $totalOutstanding <= 0 ? Loan::STATUS_REPAID : Loan::STATUS_DUE;
            $loan->save();

            $loan->receivedRepayments()->create([
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            return $loan->refresh();
        });
    }
}