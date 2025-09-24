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
            'outstanding_amount' => $amount,
            'terms' => 3,
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => now()->format('Y-m-d'),
            'status' => Loan::STATUS_DUE,
        ];
    }
}
