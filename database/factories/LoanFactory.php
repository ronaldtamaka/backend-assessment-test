<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(1000, 10000);
        return [
            'user_id' => User::factory(),
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'terms' => $this->faker->randomElement([3, 6]),
            'currency_code' => Loan::CURRENCY_SGD,
            'status' => Loan::STATUS_DUE,
            'processed_at' => $this->faker->date(),
        ];
    }

    /**
     * Configure factory to keep outstanding_amount in sync with amount
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Loan $loan) {
            if ($loan->outstanding_amount != $loan->amount) {
                $loan->update(['outstanding_amount' => $loan->amount]);
            }
        });
    }
}
