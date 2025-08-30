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
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        return DB::transaction(function () use ($user, $amount, $currencyCode, $terms, $processedAt) {
            $loan = Loan::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'outstanding_amount' => $amount,
                'currency_code' => $currencyCode,
                'terms' => $terms,
                'processed_at' => Carbon::parse($processedAt)->toDateString(),
                'status' => Loan::STATUS_DUE,
            ]);

            $baseRepayment = intdiv($amount, $terms);
            $totalRepayments = 0;

            for ($i = 1; $i < $terms; $i++) {
                ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $baseRepayment,
                    'outstanding_amount' => $baseRepayment,
                    'currency_code' => $currencyCode,
                    'due_date' => Carbon::parse($processedAt)->addMonths($i)->toDateString(),
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
                $totalRepayments += $baseRepayment;
            }

            $lastRepaymentAmount = $amount - $totalRepayments;
            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $lastRepaymentAmount,
                'outstanding_amount' => $lastRepaymentAmount,
                'currency_code' => $currencyCode,
                'due_date' => Carbon::parse($processedAt)->addMonths($terms)->toDateString(),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);

            return $loan;
        });
    }

    public function repayLoan(Loan $loan, int $receivedAmount, string $currencyCode, string $receivedAt): Loan
    {
        return DB::transaction(function () use ($loan, $receivedAmount, $currencyCode, $receivedAt) {
            $freshLoan = Loan::lockForUpdate()->findOrFail($loan->id);
            $parsedReceivedAt = Carbon::parse($receivedAt);

            $amountToPay = $receivedAmount;

            $repayments = $freshLoan->scheduledRepayments()
                ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
                ->orderBy('due_date', 'asc')
                ->get();

            foreach ($repayments as $repayment) {
                if ($amountToPay <= 0) {
                    break;
                }

                $pay = min($amountToPay, $repayment->outstanding_amount);

                $repayment->outstanding_amount -= $pay;
                if ($repayment->outstanding_amount === 0) {
                    $repayment->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $repayment->status = ScheduledRepayment::STATUS_PARTIAL;
                }
                $repayment->save();

                $amountToPay -= $pay;
            }

            ReceivedRepayment::create([
                'loan_id' => $freshLoan->id,
                'amount' => $receivedAmount,
                'currency_code' => $currencyCode,
                'received_at' => $parsedReceivedAt->toDateString(),
            ]);

            // Jika belum lunas semua, kurangi outstanding sesuai pembayaran
            $remaining = $freshLoan->scheduledRepayments()
                ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
                ->count();

            if ($remaining === 0) {
                $freshLoan->outstanding_amount = 0;
                $freshLoan->status = Loan::STATUS_REPAID;
                $freshLoan->save();
            } else {
                $freshLoan->decrement('outstanding_amount', $receivedAmount);
            }

            return $freshLoan->refresh();
        });
    }
}
