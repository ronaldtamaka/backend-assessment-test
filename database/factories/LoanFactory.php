<?php

namespace Database\Factories;

use App\Models\Loan;
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
            'user_id' => \App\Models\User::factory(),
            'amount' => $amount,
            'terms' => $this->faker->numberBetween(3, 6),
            'outstanding_amount' => $amount,
            'currency_code' => $this->faker->randomElement([Loan::CURRENCY_SGD, Loan::CURRENCY_VND]),
            'processed_at' => $this->faker->date(),
            'status' => Loan::STATUS_DUE,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Loan $loan) {
            // Ensure outstanding_amount matches amount if not explicitly set
            if (!isset($this->attributes['outstanding_amount'])) {
                $loan->outstanding_amount = $loan->amount;
            }
        });
    }
}
