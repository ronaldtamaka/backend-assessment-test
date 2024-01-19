<?php

namespace Database\Factories;

use App\Models\User;
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
        return [
            // TODO: Complete factory
            'user_id' => User::factory(),
            'amount' => $this->faker->randomNumber(),
            'terms' => $this->faker->randomNumber(),
            // 'outstanding_amount' => $this->faker->randomNumber(),
            'outstanding_amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency_code' => $this->faker->currencyCode,
            // 'processed_at' => $this->faker->dateTime,
            'processed_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            // 'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'terms' => $this->faker->randomElement([3, 6]),
        ];
    }
}
