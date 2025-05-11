<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

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
        $this->validateLoanCreationData($terms, $currencyCode, $amount);

        /** @var Loan $loan */
        $loan = $user->loans()->create([
            'amount' => $amount,
            'terms' => $terms,
            'currency_code' => $currencyCode,
            'processed_at' => Carbon::parse($processedAt)->format('Y-m-d'),
        ]);

        $loan->scheduledRepayments()->createMany(
            $this->prepareSchedules($loan)
        );

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
        /** @var ReceivedRepayment $received */
        $received = $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => Carbon::parse($receivedAt)->format('Y-m-d'),
        ]);

        $amountCurrency = $this->convertAmountToLoanCurrency($amount, $currencyCode, $loan->currency_code);
        $paidPayments = $loan
            ->scheduledRepayments
            ->where('status', '<>', ScheduledRepayment::STATUS_DUE);

        $paidAmount = $paidPayments->sum('amount') - $paidPayments->sum('outstanding_amount');
        $outstanding = $loan->outstanding_amount - ($paidAmount + $amountCurrency);

        $loan->forceFill(['outstanding_amount' => $outstanding])->save();
        $loan->syncScheduledRepaymentsWithAmount($amountCurrency);

        return $received;
    }

    /**
     * Validate loan creation data.
     *
     * @param  int  $terms
     * @param  string  $currencyCode
     * @param  int  $amount
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateLoanCreationData(int $terms, string $currencyCode, int $amount): void
    {
        if (! in_array($terms, [3, 6])) {
            throw new InvalidArgumentException('Loan terms must be either 3 or 6 months.');
        }

        $this->ensureValidCurrencyCode($currencyCode);

        $this->ensureValidAmount($amount);
    }

    /**
     * Ensure the provided currency code is valid.
     *
     * @param  string  $currencyCode
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function ensureValidCurrencyCode(string $currencyCode)
    {
        if (! in_array($currencyCode, Loan::CURRENCIES)) {
            $message = sprintf('The currency code must be in: %s.', implode(', ', Loan::CURRENCIES));
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Ensure the provide amount is valid.
     *
     * @param  int  $amount
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function ensureValidAmount(int $amount)
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0.');
        }
    }

    private function prepareSchedules(Loan $loan): array
    {
        $installment = intdiv($loan->amount, $loan->terms);
        $remainder = $loan->amount % $loan->terms;
        $schedules = [];

        for ($i = 1; $i <= $loan->terms; $i++) {
            $monthly = $i === $loan->terms ? $installment + $remainder : $installment;
            $dueDate = Carbon::parse($loan->processed_at)->addMonths($i)->format('Y-m-d');

            $schedules[] = [
                'amount' => $monthly,
                'due_date' => $dueDate,
                'currency_code' => $loan->currency_code,
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }

        return $schedules;
    }

    private function convertAmountToLoanCurrency(int $amount, string $from, string $to)
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = 1; // need to get from database or datasource

        return $amount * $rate;
    }
}
