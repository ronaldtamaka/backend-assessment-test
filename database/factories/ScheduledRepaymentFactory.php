<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledRepayment>
 */
class ScheduledRepaymentFactory extends Factory
{
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => 1000,
            // Key fix: Don't set outstanding_amount here
            // Let it be handled by the model or explicit test setup
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => now()->addMonth(),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }

    /**
     * Configure factory to ensure outstanding_amount matches status
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ScheduledRepayment $sr) {
            // If status is REPAID, ensure outstanding_amount is 0
            if ($sr->status === ScheduledRepayment::STATUS_REPAID && $sr->outstanding_amount != 0) {
                $sr->update(['outstanding_amount' => 0]);
            }
        });
    }

    /**
     * Indicate that the scheduled repayment is repaid.
     */
    public function repaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'outstanding_amount' => 0,
            'status' => ScheduledRepayment::STATUS_REPAID,
        ]);
    }

    /**
     * Indicate that the scheduled repayment is partially paid.
     */
    public function partial(int $outstandingAmount): static
    {
        return $this->state(fn (array $attributes) => [
            'outstanding_amount' => $outstandingAmount,
            'status' => ScheduledRepayment::STATUS_PARTIAL,
        ]);
    }
}   
