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
            'terms' => $this->faker->randomElement([3, 6]),
            'outstanding_amount' => $amount,
            'currency_code' => $this->faker->randomElement(['SGD', 'VND', 'IDR', 'THB']),
            'processed_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'status' => Loan::STATUS_DUE,
        ];
    }
}
