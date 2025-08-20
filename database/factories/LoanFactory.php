<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(1000, 10000);
        $terms = $this->faker->randomElement([3, 6]);
        $processedAt = Carbon::now()->subDays(rand(1, 10))->format('Y-m-d');

        return [
            'user_id' => User::factory(),
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $this->faker->randomElement([Loan::CURRENCY_SGD, Loan::CURRENCY_VND]),
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ];
    }

    /**
     * Ensure outstanding_amount is always set to the same value as amount
     */
    public function configure()
    {
        return $this->afterMaking(function (Loan $loan) {
            $loan->outstanding_amount = $loan->amount;
        })->afterCreating(function (Loan $loan) {
            $loan->outstanding_amount = $loan->amount;
            $loan->save();
        });
    }
}
